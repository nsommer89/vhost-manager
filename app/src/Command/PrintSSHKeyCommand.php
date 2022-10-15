<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Lib\DatabaseConnection;
use App\Lib\SSHWrapper;

#[AsCommand(
    name: 'printkey',
    description: 'Prints the specific SSH key.',
    hidden: false,
    aliases: ['keyprint'],
)]
class PrintSSHKeyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $keyId = $input->getArgument('keyId');

        if (!SSHWrapper::userExists($username)) {
            $output->writeln('<error>'.__('User :username does not exist.', ['username' => $username]).'</error>');
            return Command::FAILURE;
        }

        if (empty($keyId)) {
            $output->writeln('<error>'.__('Key ID is required.').'</error>');
            return Command::FAILURE;
        }

        try {
            // Create a database connection
            $db = DatabaseConnection::getInstance();

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        $query = $db->getConnection()->query('SELECT * FROM ssh_keys WHERE username = "'.$username.'" AND keyid = "'.$keyId.'" ORDER BY keyid DESC LIMIT 1');

        if ($query->rowCount() == 0) {
            $output->writeln('<error>'.__('Key ID :keyId does not exist or that key does not belong to the provided username.', ['keyId' => $keyId]).'</error>');
            return Command::FAILURE;
        }

        $keyEntity = $query->fetch();
    
        $publicKey = file_get_contents($keyEntity['file_path'] . '.pub');

        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $io->title(__('SSH Key: :keyId (:username)', ['username' => $username, 'keyId' => $keyId]));

        // Check if the key exists

        $output->writeln(__('Key ID: :keyId', ['keyId' => $keyId]));
        $output->writeln(__('Username: :username', ['username' => $username]));
        $output->writeln(__('Created At: :createdAt', ['createdAt' => $keyEntity['created_at']]));
        $output->writeln(__('Key: :path.pub', ['path' => sprintf('/home/%s/.ssh/%s', $username, $keyId)]));
        $output->writeln(__('Description:'.PHP_EOL.':description', ['description' => $keyEntity['description']]));

        echo PHP_EOL;
        $output->writeln('<fg=cyan>'.$publicKey.'</>');
        echo PHP_EOL;

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(__('Prints the specific SSH key.'))
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                __('The username of the vhost user you want to create a SSH key for.')
            )->addArgument(
                'keyId',
                InputArgument::REQUIRED,
                __('The key ID of the SSH key you want to print.')
            );
    }
}