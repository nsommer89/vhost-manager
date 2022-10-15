<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use App\Lib\DatabaseConnection;

#[AsCommand(
    name: 'start',
    description: 'VhostManager is a tool for managing virtual hosts on a server.',
    hidden: false,
    aliases: ['start']
)]
class StartUpCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        hook_brand($this->getApplication()->getName(), getenv('VHOST_MANAGER_VERSION'), getenv('LOCALE'));

        try {
            $db = DatabaseConnection::getInstance();
        } catch (\Exception $e) {
            $io->error($e->getMessage() . ' ' . __('Please check your database connection settings.'));
            echo json_encode($e->getTrace());
            return Command::FAILURE;
        }

        $io->text(__('Commands'));
        $io->definitionList(
            '<fg=white;bg=blue;>'.__('Virtual Hosts').'</>',
            ['<fg=yellow>vhost add</>' => __('Add a new virtual host.')],
            ['<fg=yellow>vhost list</>' => __('List all virtual hosts.')],
            ['<fg=yellow>vhost info <username></>' => __('Prints out information about a given virtual host.')],
            ['<fg=yellow>vhost remove <username></>' => __('Removes a virtual host.')],
            new TableSeparator(),
            '<fg=white;bg=blue;>'.__('Authorized keys').'<fg=white;> '.__('Add and remove keys that have access to this server.').'</></>',
            ['<fg=yellow>vhost keys <username></>' => __('List all authorized keys for a given virtual host/user.')],
            ['<fg=yellow>vhost addkey <username></>' => __('Give access to a user by adding an SSH-key.')],
            ['<fg=yellow>vhost removekey <username> <key-id></>' => __('Removes access to this server for a given SSH-key.')],
            new TableSeparator(),
            '<fg=white;bg=blue;>'.__('Create SSH keys').'<fg=white;> '.__('Create a SSH-key for a deployment pipeline or a git-repo.').'</></>',
            ['<fg=yellow>vhost genkey <username></>' => __('Creates a SSH-key for a given virtual host/user.')],
            ['<fg=yellow>vhost printkey <username> <key-id></>' => __('Prints out a SSH pubkey to the console.')],
            new TableSeparator(),
            '<fg=white;bg=blue;>'.__('System').'</>',
            ['<fg=yellow>vhost update</>' => __('Update the system.')],
            ['<fg=yellow>vhost status|info</>' => __('Prints out the status of the system.')],
        );

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp(__('This command will start the VhostManager...'))
        ;
    }
}