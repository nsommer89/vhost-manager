<?php
namespace App\Command;

use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Lib\DatabaseConnection;
use App\Lib\SSHWrapper;

#[AsCommand(
    name: 'genkey',
    description: 'Generates a new SSH key.',
    hidden: false,
    aliases: ['keygen'],
)]
class GenerateSSHKeyCommand extends Command
{
    use LockableTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln(__('The command is already running in another process.'));
            return Command::FAILURE;
        }

        $username = $input->getArgument('username');
        $keyId = substr(hash('sha256', bin2hex(random_bytes(64)) . $username), 0, 8);
        if (!SSHWrapper::userExists($username)) {
            $output->writeln('<error>'.__('User :username does not exist.', ['username' => $username]).'</error>');
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $io->title(__('Create a SSH Key for :username', ['username' => $username]));

        // Add a unix user
        $io->section(__('Generate SSH key'));

        $io->listing([
            __('This will generate a new SSH key for the user :username.', ['username' => $username]),
            __('The key will be stored in the users .ssh home directory.'),
            __('The key will be named <key-id> and <key-id>.pub.'),
        ]);

        $description = '';
        while (empty($description)) {
            $description = $helper->ask($input, $output, new Question('<options=bold>' . __('Describe this key') . ':</>', ''));
        }

        SSHWrapper::generateSSHKey($username, sprintf('%s', $keyId));

        $output->writeln(__('SSH key generated successfully.'));
        $output->writeln(__('The key ID is :key-id', ['key-id' => $keyId]));
        $output->writeln(__('Saved to :path and :path.pub', ['path' => sprintf('/home/%s/.ssh/%s', $username, $keyId)]));

        try {
            // Create a database connection
            $db = DatabaseConnection::getInstance();

            $db->getConnection()->beginTransaction();

            // Prepare statement
            $stmt = $db
            ->getConnection()
            ->prepare('INSERT INTO ssh_keys (username, keyid, file_path, description, created_at, updated_at) VALUES (:username, :keyid, :file_path, :description, :created_at, :updated_at)');
            
            // Set parameter variables
            $now = date('Y-m-d H:i:s');
            $filePath = sprintf('/home/%s/.ssh/%s', $username, $keyId);

            // Bind parameters
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':keyid', $keyId);
            $stmt->bindParam(':file_path', $filePath);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':created_at', $now);
            $stmt->bindParam(':updated_at', $now);

            // Execute
            $stmt->execute();

            // Commit
            $db->getConnection()->commit();

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        $io->info(__('Database updated successfully.'));
        $this->release();
        $io->success(__('Done.'));
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(__('This command can be used to create a new ssh key and adds it to ssh-config.'))
        ->addArgument(
            'username',
            InputArgument::REQUIRED,
            __('The username of the vhost user you want to create a SSH key for.')
        );
    }
}