<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'update',
    description: 'Check for updates.',
    hidden: false,
    aliases: ['update'],
)]
class CheckForUpdatesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $jsonUrl = getenv('VERSIONS_JSON_FILE_URL');
            $silent = $input->getArgument('silent') ?? false;

            if ($silent === true || $silent === 'true') {
                $silent = true;
            }

            if (!$silent) {
                hook_brand($this->getApplication()->getName(), getenv('VHOST_MANAGER_VERSION'), getenv('LOCALE'));
                $output->writeln('<fg=yellow>Checking for updates...</>');
            }

            $client = HttpClient::create();
            $response = $client->request('GET', $jsonUrl);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new \Exception('Could not fetch url: ' . $jsonUrl);
                return Command::FAILURE;
            }

            $result = $response->toArray();

            if (empty($result)) {
                throw new \Exception('Could not fetch url: ' . $jsonUrl);
                return Command::FAILURE;
            }

            $versions = $result['versions'] ?? null;
            $latestStable = $versions[$result['stable']] ?? null;

            if (empty($latestStable)) {
                throw new \Exception('Could not fetch url: ' . $jsonUrl);
                return Command::FAILURE;
            }

            if ($result['stable'] === getenv('VHOST_MANAGER_VERSION')) {
                if (!$silent) {
                    $output->writeln('<fg=green>You are currently running the latest version of VhostManager.</>');
                }
                return Command::SUCCESS;
            }

            $confirm = false;
            if (!$silent) {
                $output->writeln('<fg=yellow>Current version: '.getenv('VHOST_MANAGER_VERSION').'</>');
                $io = new SymfonyStyle($input, $output);
                $io->text(__('<fg=bright-magenta>NEW VERSION!</>'));
                $io->definitionList(
                    ['<fg=blue>Latest Version</>' => 'v'.$result['stable']],
                    ['<fg=blue>Release Date</>' => $latestStable['date']],
                    ['<fg=blue>Summary</>' => $latestStable['summary']],
                );

                $helper = $this->getHelper('question');
                $confirm = $helper->ask($input, $output, new ConfirmationQuestion('<options=bold>'.__('Do you want to update?').'</> (y/n)', false, '/^(y|j)/i'));
            }

            if ($confirm || $silent === true) {
                $output->writeln('<fg=yellow>Updating...</>');

                // Update stuff
                \exec('sh '.getenv('VHOST_MANAGER_DIR').'/update-install/bash-update.sh '.$result['stable'].' '.getenv('VHOST_MANAGER_DIR').' '.getenv('COMPOSER_INSTALL_PATH').' &> /dev/null', $updateResult, $updateExitCode);

                if ($updateExitCode !== 0) {
                    $output->writeln('<fg=red>Update failed!</>');
                    return Command::FAILURE;
                }

                $output->writeln('<fg=yellow>Done.</>');
            } else {
                $output->writeln('<fg=yellow>Update aborted.</>');
            }

            if (empty($confirm)) {
                $output->writeln('<fg=red>Bye!</>');
                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            if (!$silent) {
                $output->writeln('<fg=red>Unable to check for updates: '.$e->getMessage().'</>');
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Check for updates.')
            ->addArgument('silent', InputArgument::OPTIONAL, 'Silent mode.');
    }
}
