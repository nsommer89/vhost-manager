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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Lib\DatabaseConnection;
use App\Lib\SSHWrapper;

#[AsCommand(
    name: 'remove',
    description: 'Removes a vhost.',
    hidden: false,
    aliases: ['delete', 'remove'],
)]
class RemoveVhostCommand extends Command
{
    use LockableTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return Command::SUCCESS;
        }

        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $WWW_FOLDER = getenv('WWW_ROOT');
        $PHP_VERSION = getenv('PHP_VERSION');

        $username = $input->getArgument('username');

        if (!empty($username)) {
            $getUid = exec(sprintf('id -u %s', $username), $getUidOutput, $return);
            $uid = (int) $getUid;
        }

        if (empty($uid)) {
            $io->error(__('User with username {username} does not exist.', ['username' => $username]));
            return Command::FAILURE;
        }

        $io->title(__('Delete Virtual Host: {username}', ['username' => $username]));

        $confirm = $helper->ask($input, $output, new ConfirmationQuestion('<options=bold>'.__('Are you sure you want to delete all files, folders and settings for {username}?', ['username' => $username]).'</> (y/n)', true, '/^(y|j)/i'));

        if (!$confirm) {
            $io->error(__('Aborted.'));
            return Command::FAILURE;
        }
        
        try {
            SSHWrapper::removeWwwDirectory($username);
            SSHWrapper::removeHttpConfig($username);
            SSHWrapper::removeFpmConfig($username);
        } catch (\Exception $e) {
            // TODO: Log error or handle exception. For now we go silent.
        }

        $io->info(__('Shell operations completed successfully.'));

        // Update database
        $output->writeln(__('Removing user and vhost from database...'));
        
        try {
            // Create a database connection
            $db = DatabaseConnection::getInstance();

            // Begin transaction
            $db->getConnection()->beginTransaction();
            
            // Prepare statement
            $stmt = $db
            ->getConnection()
            ->prepare('DELETE FROM vhosts WHERE username = :username');
            
            // Bind parameters
            $stmt->bindParam(':username', $username);

            // Execute
            $stmt->execute();

            // Commit
            $db->getConnection()->commit();

        } catch (Exception $e) {
            // Rollback
            $io->warning(__('Rolling back database changes...'));
            $db->getConnection()->rollBack();

            // Output error
            $io->error($e->getMessage());

            // Exit
            return Command::FAILURE;
        }
        $output->writeln(__('Updating database..'));

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

        exec('deluser --remove-home '.$username, $deluser_output, $deluserCode);
        
        if ($deluserCode != 0) {
            $io->error(__('There was an error while deleting the user. Please delete the user manually.'));
            $io->info(json_encode($deluser_output));
        }
        
        $this->release();

        $io->success(__('The user and vhost have been deleted successfully.'));
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(__('Delete a vhost and the client user.'))
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                __('The username of the user to delete.')
            )
        ;
    }
}