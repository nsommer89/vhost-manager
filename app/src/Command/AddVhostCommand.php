<?php
namespace App\Command;

use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Lib\DatabaseConnection;
use App\Lib\SSHWrapper;

#[AsCommand(
    name: 'add',
    description: 'Creates and add a new vhost.',
    hidden: false,
    aliases: ['add'],
)]
class AddVhostCommand extends Command
{
    use LockableTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln(__('The command is already running in another process.'));
            return Command::FAILURE;
        }

        try {
            // Create a database connection
            $db = DatabaseConnection::getInstance();

            // Getting the next fastcgi port
            $fastcgi_port_query = $db->getConnection()->query('SELECT * FROM php_fastcgi_port_counter ORDER BY current DESC LIMIT 1');
            $current_fastcgi_port = $fastcgi_port_query->fetch()['current'];
            $next_fastcgi_port = $current_fastcgi_port + 1;

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $io->title(__('Create a new vhost on this server'));

        // Add a unix user
        $io->section(__('Add a user'));
        $io->note([
            __('The users are used to separate the different vhosts. Think of it as as pr. project name.'),
        ]);
        $io->listing([
            __('Only small letters and numbers are allowed.'),
            __('No space or special characters.'),
            __('Can\'t start with a number.'),
            __('Should be descriptive to the client, unique and one word.')
        ]);
        $usernameQuestion = function () use ($helper, $input, $output) {
            return $helper->ask($input, $output, new Question('<options=bold>' . __('Username') . ':</>', ''));
        };
        $username = $usernameQuestion();

        while($username == '' || !preg_match('/^[a-z0-9]+$/', $username) || (strlen($username) > 0 && is_numeric($username[0])) ) {
            $output->writeln('<error>'.__('Invalid username').'</error>');
            $username = $usernameQuestion();
        }
        while (SSHWrapper::userExists($username)) {
            $output->writeln('<error>' . __('User :username already exists.', ['username' => $username]) . '</error>');
            $username = $usernameQuestion();
        }

        // Add domains
        $domainList = [];
        $done = false;
        while (!$done) {
            $domainQuestion = function () use ($helper, $input, $output, $io) {
                $io->section(__('Add domain(s)'));
                $io->listing([
                    __('Only valid FQDN domain names.'),
                    __('Separate multiple domains with spaces.'),
                    __('The first domain will be the primary domain.'),
                    __('Remove existing domains by simply typing them again.'),
                ]);
                $output->writeln(__('Press \'q\' to finish adding domains.'));

                return $helper->ask($input, $output, new Question('<options=bold>' . __('Add Domain') . ':</>', ''));
            };

            $domain = strtolower($domainQuestion());

            if ($domain == 'q') {
                if (count($domainList) == 0) {
                    $output->writeln('<error>'.__('At least one domain is required.').'</error>');
                } else {
                    $io->section(__('Confirm domains'));
                    $io->listing($domainList);
                    $done = $helper->ask($input, $output, new ConfirmationQuestion('<options=bold>'.__('Are the domains correct?').'</> (y/n)', false, '/^(y|j)/i'));
                }
            } else {
                $key = array_search($domain, $domainList);
                if ($key !== false) {
                    unset($domainList[$key]);
                    $output->writeln('<options=bold>'.__('Domain was removed').'</>');
                } else {
                    if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/', $domain)) {
                        $output->writeln('<error>'.__('Invalid domain').': ' . $domain . '</error>');
                    } else {

                        // add the domain to the list
                        $domainList[] = $domain;
                        $domainList[] = 'www.' . $domain;
                        $output->writeln('<options=bold>'.__('Domain added').'</>');

                        // clean up the list
                        $domainList = array_filter($domainList, function($value) { return $value !== ''; });
                        $domainList = array_unique($domainList);
                    }
                }
            }
        }
        $domains = [
            'str_value' => implode(' ', $domainList),
            'domains' => $domainList
        ];

        // Ask whether the user wants to add SSL to each domain
        $io->section(__('Enable SSL'));
        $io->note([
            __('Select whether you want to add SSL to each domain.'),
        ]);
        $question = new ConfirmationQuestion('<options=bold>'.__('Enable LetsEncrypt SSL?').'</> (y/n)', false, '/^(y|j)/i');
        $ssl = $helper->ask($input, $output, $question);

        $sslDomainList = [];
        if ($ssl) {
            $sslQuestion = function () use ($helper, $input, $output, $domains) {
                $list = [];
                foreach ($domains['domains'] as $domain) {
                    $ssl = $helper->ask($input, $output, new ConfirmationQuestion('<options=bold>'.__('Add LetsEncrypt SSL to {domain}?', ['domain' => $domain]).'</> (y/n)', true, '/^(y|j)/i'));
                    if ($ssl) {
                        $list[] = $domain;
                    }
                }
                return $list;
            };
            $sslDomainList = $sslQuestion();
            while(empty($sslDomainList)) {
                $output->writeln('<error>'.__('Invalid input').'</error>');
                $sslDomainList = $sslQuestion();
            }
            $output->writeln('');
        }

        $io->section(__('Shell operations'));
        $io->note([
            __('Running shell operations to create the vhost on the server.'),
        ]);

        $nginx_config_path = '/etc/nginx/sites-available/' . $username . '.conf';
        $fpm_config_path = '/etc/php/'.getenv('PHP_VERSION').'/fpm/pool.d/' . $username . '.conf';

        // Do all the shell operations
        try {
            SSHWrapper::adduser($username, false, null, true);
            SSHWrapper::createDotSshDir($username);
            SSHWrapper::createWWWDir(getenv('WWW_ROOT'), $username);
            SSHWrapper::createHttpServerConfig($username, $domains['str_value'], $nginx_config_path, $next_fastcgi_port);
            SSHWrapper::createFpmConfig($username, $fpm_config_path, $next_fastcgi_port);

            if (count($sslDomainList) > 0) {
                $output->writeln(__('Certbot is installing certificates for the following domains:'));
                $io->listing($sslDomainList);
                $output->writeln(__('Waiting for Certbot to finish...'));
                try {
                    $result = SSHWrapper::runCertbot($sslDomainList, true, 'nginx');

                    if (!$result) {
                        $output->writeln(__('Certbot failed to install certificates for the domains'));
                        $output->writeln(__('Please run the command manually to install the certificates.'));
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                    $output->writeln(__('Certbot failed to install certificates for the following domains:'));
                    $output->writeln(__('Please run the command manually to install the certificates.'));
                }
            }
        } catch (Exception $e) {
            $io->warning(__('Rolling back changes...'));
            SSHWrapper::removeWwwDirectory($username);
            SSHWrapper::removeHttpConfig($username);
            SSHWrapper::removeFpmConfig($username);
            exec('deluser --remove-home ' . $username);
            SSHWrapper::restartFpm();
            SSHWrapper::restartHttpServer();
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        // Get the user's uid
        $uid = SSHWrapper::getUidByUsername($username);

        $io->info(__('Shell operations completed successfully.'));

        // Update database
        $output->writeln(__('Updating database...'));
        
        try {
            // Begin transaction
            $db->getConnection()->beginTransaction();

            // Update fastcgi port
            $db->getConnection()->query('UPDATE php_fastcgi_port_counter SET current='.$next_fastcgi_port.' WHERE current = ' . $current_fastcgi_port);
            
            // Prepare statement
            $stmt = $db
            ->getConnection()
            ->prepare('INSERT INTO vhosts (username, userid, www_root, php_version, domains_json, www_suffix_domains_json, ssl_domains_json, nginx_config_path, fpm_config_path, created_at, updated_at) VALUES (:username, :userid, :www_root, :php_version, :domains_json, :www_suffix_domains_json, :ssl_domains_json, :nginx_config_path, :fpm_config_path, :created_at, :updated_at)');
            
            // Set parameter variables
            $now = date('Y-m-d H:i:s');
            $domains_json = json_encode($domains['domains']);
            $www_suffix_domains_json = json_encode($domains['domains']);
            $ssl_domains_json = json_encode($sslDomainList);
            $www_root = getenv('WWW_ROOT').'/'.$username;
            $php_version = getenv('PHP_VERSION');

            // Bind parameters
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':userid', $uid);
            $stmt->bindParam(':www_root', $www_root);
            $stmt->bindParam(':php_version', $php_version);
            $stmt->bindParam(':domains_json', $domains_json);
            $stmt->bindParam(':www_suffix_domains_json', $www_suffix_domains_json);
            $stmt->bindParam(':ssl_domains_json', $ssl_domains_json);
            $stmt->bindParam(':nginx_config_path', $nginx_config_path);
            $stmt->bindParam(':fpm_config_path', $fpm_config_path);
            $stmt->bindParam(':created_at', $now);
            $stmt->bindParam(':updated_at', $now);

            // Execute
            $stmt->execute();

            // Commit
            $db->getConnection()->commit();

        } catch (Exception $e) {
            // Rollback
            $io->warning(__('Rolling back database changes...'));
            $db->getConnection()->rollBack();

            SSHWrapper::removeWwwDirectory($username);
            SSHWrapper::removeHttpConfig($username);
            SSHWrapper::removeFpmConfig($username);
            exec('deluser --remove-home ' . $username);
            SSHWrapper::restartFpm();
            SSHWrapper::restartHttpServer();

            // Output error
            $io->error($e->getMessage());

            // Exit
            return Command::FAILURE;
        }

        $io->info(__('Database updated successfully.'));

        $io->section(__('Restarting services'));
        $io->note([
            __('Restarting services to apply the changes.'),
        ]);

        try {
            SSHWrapper::restartFpm();
            SSHWrapper::restartHttpServer();
         } catch (Exception $e) {
            $io->note([
                __('INFO: There was an error while restarting nginx or php{php_version}-fpm. Please restart them manually.', ['php_version' => getenv('PHP_VERSION')]),
            ]);
         }

        $this->release();
        $io->success(__('Done.'));
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(__('This command can be used to create a new vhost...'))
        ;
    }
}