<?php

namespace Scottymeuk\Backup\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates backup.phar to the latest version')
            ->addArgument(
                'location',
                InputArgument::REQUIRED,
                'Where is the update file hosted?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tmp_file = tempnam(sys_get_temp_dir(), 'backup.phar');
        $get_tmp_file = @copy($input->getArgument('location'), $tmp_file);
        if (! $get_tmp_file) {
            $output->writeln('<error>Could not get remote file</error>');
            exit;
        }

        $local_file = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];

        // Get the local version
        $local_version = trim(shell_exec('php ' . $local_file . ' --version'));
        $local_version = str_replace('Backup version ', null, $local_version);

        // Get the remote version
        $remote_version = trim(shell_exec('php ' . $tmp_file . ' --version'));
        $remote_version = str_replace('Backup version ', null, $remote_version);

        // Check that there is an update
        if ($local_version === $remote_version) {
            $output->writeln('No update found');
            exit;
        }

        chmod($tmp_file, 0755);
        rename($tmp_file, $local_file);

        $output->writeln('<info>Updated from "' . $local_version . '" to "' . $remote_version . '"</info>');
    }
}
