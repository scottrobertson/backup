<?php
namespace Scottymeuk\Backup\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MySQLCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('backup:mysql')
            ->setDescription('Backup all MySQL databases')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Backing up databases');
    }
}