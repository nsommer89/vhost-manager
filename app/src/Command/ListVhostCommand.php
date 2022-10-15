<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use App\Lib\DatabaseConnection;

#[AsCommand(
    name: 'list',
    description: 'Lists all vhosts.',
    hidden: false,
    aliases: ['list'],
)]
class ListVhostCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $db = DatabaseConnection::getInstance();

        $query = $db->getConnection()->query('SELECT * FROM vhosts');
        
        foreach ($query as $row) {
            $domains = implode(', ', json_decode($row['domains_json']));
            $table->addRow([$row['username'], $domains, $row['www_root'], 'PHP ' . $row['php_version'], $row['updated_at']]);
        }

        if ($query->rowCount() == 0) {
            $table->addRow([new TableCell('<options=bold>'.__('There are no virtual hosts at the moment. Type :command to add one.', ['command' => '<fg=magenta>vhost add</>']).'</>', ['colspan' => 5])]);
        }

        $table->setHeaderTitle(__('Virtual Hosts'))
            ->setHeaders([__('User'), __('Hostnames'), __('Document Root'), __('PHP Version'), __('Last Updated')]);
          $table->render();

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command will list all vhosts...')
        ;
    }
}