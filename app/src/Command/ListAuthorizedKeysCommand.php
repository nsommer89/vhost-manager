<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use App\Lib\DatabaseConnection;
use App\Lib\Helpers\Text;

#[AsCommand(
    name: 'keys',
    description: 'List authorized keys of a vhost user.',
    hidden: false,
    aliases: ['keys'],
)]
class ListAuthorizedKeysCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $db = DatabaseConnection::getInstance();

        $username = $input->getArgument('username');

        if (!empty($username)) {
            $query = $db->getConnection()->query('SELECT * FROM authorized_keys WHERE username = "' . $username . '"');
        } else {
            $query = $db->getConnection()->query('SELECT * FROM authorized_keys');
        }
        foreach ($query as $row) {
            $table->addRow(['<fg=cyan>'.$row['keyid'].'</>', '<fg=yellow>'.$row['username'].'</>', Text::truncate($row['description'], 40), $row['updated_at']]);
        }

        if ($query->rowCount() == 0) {
            $table->addRow([new TableCell('<options=bold>'.__('There are no authorized key on this user at the moment. Type :command to add one.', ['command' => '<fg=magenta>vhost keyadd <username></>']).'</>', ['colspan' => 4])]);
        }

        $table->setHeaderTitle(__('Authorized Keys'))
            ->setHeaders([__('Key ID'), __('Username'), __('Description'), __('Last Updated')]);
          $table->render();

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('List all authorized keys of a vhost user.')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                __('List authorized keys of a vhost user.')
            );
    }
}