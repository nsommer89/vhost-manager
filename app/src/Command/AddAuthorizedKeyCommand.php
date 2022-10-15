<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Lib\DatabaseConnection;
use App\Lib\SSHWrapper;

#[AsCommand(
    name: 'addkey',
    description: 'Add a authorized key to a vhost user.',
    hidden: false,
    aliases: ['keyadd'],
)]
class AddAuthorizedKeyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        if (!SSHWrapper::userExists($username)) {
            $output->writeln('<error>'.__('User :username does not exist.', ['username' => $username]).'</error>');
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);
        $style = new SymfonyStyle($input, $output);
        $style->title(__('Provide the public key to add to the authorized keys file of the user :username', ['username' => $username]));
        $question = new Question(__('Paste the public key:'));
        $question->setMultiline(true);
        $publicKey = null;
        while(empty($publicKey)) {
            $publicKey = $style->askQuestion($question);
        }
        $style->block($publicKey ?? '');

        $description = '';
        while (empty($description)) {
            $description = $helper->ask($input, $output, new Question('<options=bold>' . __('Describe this key') . ':</>', ''));
        }

        $keyId = substr(hash('sha256', $publicKey . bin2hex(random_bytes(32)) . $description), 0, 8);

        try {
            SSHWrapper::addAuthorizedKey($username, $publicKey, $keyId);
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return Command::FAILURE;
        }

        try {
            $db = DatabaseConnection::getInstance();
            // Begin transaction
            $db->getConnection()->beginTransaction();

            // Prepare statement
            $stmt = $db
            ->getConnection()
            ->prepare('INSERT INTO authorized_keys (username, keyid, description, created_at, updated_at) VALUES (:username, :keyid, :description, :created_at, :updated_at)');
            
            // Set parameter variables
            $now = date('Y-m-d H:i:s');

            // Bind parameters
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':keyid', $keyId);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':created_at', $now);
            $stmt->bindParam(':updated_at', $now);

            // Execute
            $stmt->execute();

            // Commit
            $db->getConnection()->commit();

        } catch (\Exception $e) {
            // Rollback
            $io->warning(__('Rolling back database changes...'));
            $db->getConnection()->rollBack();

            // TODO: Delete the vhost files, folders and configs - if they were created - Full rollback

            // Output error
            $io->error($e->getMessage());

            // Exit
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(__('With this command you can add a authorized key to a vhost user.'))
        ->addArgument(
            'username',
            InputArgument::REQUIRED,
            __('The username of the vhost user you want to add a authorized key to.')
        );
    }
}