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
    name: 'removekey',
    description: 'Removes a authorized key from a vhost user.',
    hidden: false,
    aliases: ['keyremove'],
)]
class RemoveAuthorizedKeyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        if (!SSHWrapper::userExists($username)) {
            $output->writeln('<error>'.__('User :username does not exist.', ['username' => $username]).'</error>');
            return Command::FAILURE;
        }
        $keyId = $input->getArgument('key_id');

        $helper = $this->getHelper('question');
        $style = new SymfonyStyle($input, $output);
        $style->title(__('Removing authorized key: :key from :username', ['username' => $username, 'key' => $keyId]));

        $confirm = $helper->ask($input, $output, new ConfirmationQuestion('<options=bold>'.__('Are you sure you wanto delete the key? This can\'t be undone.').'</> (y/n)', true, '/^(y|j)/i'));

        if (!$confirm) {
            $io->error(__('Aborted.'));
            return Command::FAILURE;
        }

        try {
            $sshConfigFile = file_get_contents('/home/'.$username.'/.ssh/authorized_keys');
            $start = preg_match("/\bStartKey: ".$keyId."\b/i", $sshConfigFile, $matchesStart, PREG_OFFSET_CAPTURE);
            $end = preg_match("/\bEndkey: ".$keyId."\b/i", $sshConfigFile, $matchesEnd, PREG_OFFSET_CAPTURE);

            if (!$start || !$end) {
                $output->writeln('<error>'.__('Key :key_id does not exist.', ['key_id' => $keyId]).'</error>');
                return Command::FAILURE;
            }

            if ($start && $end) {
                $start = $matchesStart[0][1];
                $end = $matchesEnd[0][1];
                $length = $end - $start;
                $sshConfigFile = substr_replace($sshConfigFile, '', $start-1, $end+14);
                $sshConfigFile = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $sshConfigFile);
                file_put_contents('/home/'.$username.'/.ssh/authorized_keys', $sshConfigFile);
            }
            //SSHWrapper::addAuthorizedKey($username, $publicKey, $description, $keyId);
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return Command::FAILURE;
        }

        try {
            $db = DatabaseConnection::getInstance();
            // Begin transaction
            $db->getConnection()->beginTransaction();

            $db->getConnection()->query('DELETE FROM authorized_keys WHERE keyid = "'.$keyId.'" ');

            // Commit
            $db->getConnection()->commit();

        } catch (\Exception $e) {
            // Rollback
            $output->writeln('<error>Rolling back database changes...</error>');
            $db->getConnection()->rollBack();

            // TODO: Delete the vhost files, folders and configs - if they were created - Full rollback

            // Output error
            $output->writeln('<error>'.$e->getMessage().'</error>');

            // Exit
            return Command::FAILURE;
        }

        $output->writeln('<info>'.__('Key :key_id removed from user :username.', ['key_id' => $keyId, 'username' => $username]).'</info>');

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
            )->addArgument(
                'key_id',
                InputArgument::REQUIRED,
                __('The id of the key you want to remove.')
            );
    }
}