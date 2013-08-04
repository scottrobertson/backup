<?php
namespace Scottymeuk\Backup\Command\Export;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput as ArrayInput;


class MongoDBCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('export:mongodb')
            ->setDescription('Backup all MongoDB databases')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Exporting MongoDB');
        $output->writeln('---------------');

        $config = $this->getApplication()->config;
        $upload = $this->getApplication()->find('dropbox:upload');

        $backup_path = ROOT . '/backups/mongo/';
        $file_name = date('ymd') . '.gz';
        $file_path = $backup_path . $file_name;
        if (! is_dir($backup_path)) {
            mkdir($backup_path, 0777, true);
        }

        $tmp_directory = sys_get_temp_dir() . '/' . date('ymd');
        if (! is_dir($tmp_directory)) {
            mkdir($tmp_directory, 0777, true);
        }

        exec(sprintf('mongodump --out=%s', $tmp_directory), $exec_output, $dump_return);
        exec(sprintf('cd %s; tar -zcvf %s . 2>&1', $tmp_directory, $file_path), $exec_output, $tar_return);

        if ($dump_return != 0 || $tar_return != 0) {
            $output->writeln('<error>Error exporting MongoDB</error>');
            return 1;
        }

        $output->writeln('> <info>Exported</info>');

        $arguments = array(
            'command' => 'dropbox:upload',
            'file' => $file_path,
            'dropbox_path'    => $config['dropbox']['path'] . 'mongo/' . $file_name,
        );

        $input = new ArrayInput($arguments);

        $returnCode = $upload->run($input, $output);
        if ($returnCode === 0) {
            $output->writeln('> <info>Sent to Dropbox</info>');
        } else {
            $output->writeln('> <error>Could not send to Dropbox</error>');
        }

        exec(sprintf('rm -rf %s', $tmp_directory));
        exec(sprintf('rm -f %s', $file_path));

    }
}
