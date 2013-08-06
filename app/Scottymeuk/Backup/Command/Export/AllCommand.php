<?php
namespace Scottymeuk\Backup\Command\Export;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArgvInput as ArgvInput;

class AllCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('export:all')
            ->setDescription('Backup using all available means (specify in config.json)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getApplication()->config;

        if (! isset($config['export'])) {
            $output->writeln('<error>You must supply an "export" array in config.json</error>');
            return 1;
        }

        if (in_array('mysql', $config['export'])) {
            $mysql = $this->getApplication()->find('export:mysql');
            $returnCode = $mysql->run($input, $output);
        }

        if (in_array('mongodb', $config['export'])) {
            $mongodb = $this->getApplication()->find('export:mongodb');
            $returnCode = $mongodb->run($input, $output);
        }

        if (in_array('folders', $config['export'])) {
            $folders = $this->getApplication()->find('export:folders');
            $returnCode = $folders->run($input, $output);
        }
    }
}
