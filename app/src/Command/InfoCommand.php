<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;
use App\Lib\DatabaseConnection;
use App\Lib\Helpers\Text;

#[AsCommand(
    name: 'info',
    description: 'Get system information.',
    hidden: false,
    aliases: ['status'],
)]
class InfoCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $db = DatabaseConnection::getInstance();
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        // the user we eventually want to get information about, or else spit out system information
        $username = $input->getArgument('username');

        $currentUser = \App\Lib\SSHWrapper::whoami();
        if ($currentUser == 'root') {
            $currentUser .= ' (!)';
        }

        hook_brand($this->getApplication()->getName(), getenv('VHOST_MANAGER_VERSION'), getenv('LOCALE'));

        if (!empty($username)) {

            $query = $db->getConnection()->query('SELECT * FROM vhosts WHERE username = "'.$username.'" ORDER BY id DESC LIMIT 1');

            if ($query->rowCount() == 0) {
                $output->writeln('<error>'.__('Key ID :keyId does not exist or that key does not belong to the provided username.', ['keyId' => $keyId]).'</error>');
                return Command::FAILURE;
            }

            $vhost = $query->fetch();

            $io->text(__('User information for :username', ['username' => $username]));
            $io->definitionList(
                ['<fg=blue>Username</>' => $vhost['username']],
                ['<fg=blue>User ID (unix uid)</>' => $vhost['userid']],
                ['<fg=blue>www-root</>' => $vhost['www_root']],
                ['<fg=blue>PHP Version</>' => $vhost['php_version']],
                ['<fg=blue>Domains</>' => implode(' ', json_decode($vhost['domains_json']))],
                ['<fg=blue>Nginx config</>' => $vhost['nginx_config_path']],
                ['<fg=blue>PHP-fpm config</>' => $vhost['fpm_config_path']],
                ['<fg=blue>Created At</>' => $vhost['created_at']],
            );

            return Command::SUCCESS;
        }

        $io->text(__('System Info'));
        $io->definitionList(
            ['<fg=blue>Executing User</>' => $currentUser],
            ['<fg=blue>'.$this->getApplication()->getName().' version</>' => $this->getApplication()->getVersion()],
            ['<fg=blue>PHP Version</>' => getenv('PHP_VERSION')],
            ['<fg=blue>Web Server</>' => getenv('WEBSERVER')],
            ['<fg=blue>Application Locale</>' => getenv('LOCALE')],
        );

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Get information about the system. Add username to get information about a specific user.')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                __('Get information about a specific user.')
            );
    }
}