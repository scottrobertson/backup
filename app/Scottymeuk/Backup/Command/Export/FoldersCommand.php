<?php
namespace Scottymeuk\Backup\Command\Export;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput as ArrayInput;


class FoldersCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('export:folders')
            ->setDescription('Backup all folders specified in config.json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Exporting Folders');
        $output->writeln('---------------');
        $output->writeln('');

        $config = $this->getApplication()->config;
        $upload = $this->getApplication()->find('dropbox:upload');


        if (! isset($config['folders']) || ! count($config['folders'])) {
            $output->writeln('<error>No folders specified in config.json</error>');
            return 1;
        }

        $exclude_folders = null;
        if (isset($config['exclude_folders'])) {
            foreach ($config['exclude_folders'] as $exclude) {
                $exclude_folders .= $exclude . "\n";
            }
        }

        $exclude_file = BACKUPS . '/folders/exclude.txt';
        file_put_contents($exclude_file, $exclude_folders);

        $group_backup_directory = 'folders/' . date('ym') . '/';
        $backup_directory = BACKUPS . '/' . $group_backup_directory . '/';

        if (! is_dir($backup_directory)) {
            mkdir($backup_directory, 0777, true);
        }

        foreach ($config['folders'] as $folder) {

            $output->writeln($folder);

            $clean_name = strtolower(ltrim($folder, '/'));
            $clean_name = str_replace('/', '-', $clean_name) . '.gz';
            $backup_file_path = $backup_directory . $clean_name;

            if (! is_dir($folder)) {
                $output->writeln(sprintf('<error>%s is not a folder</error>', $folder));
                continue;
            }

            exec(
                sprintf(
                    'tar -zcvf %s -X %s %s 2>&1',
                    $backup_file_path,
                    $exclude_file,
                    $folder
                ),
                $exec_output,
                $tar_return
            );

            if ($tar_return != 0) {
                $output->writeln(sprintf('<error>Error backing up %s</error>', $folder));
                continue;
            }

            $output->writeln(sprintf(
                '> <info>Compressed (%smb)</info>',
                round(filesize($backup_file_path) / 1024 / 1024)
            ));

            // Setup Dropbox arguments
            $arguments = array(
                'command' => 'dropbox:upload',
                'file' => $backup_file_path,
                'dropbox_path' => $config['dropbox']['path'] . $group_backup_directory . '/' . $clean_name,
            );

            // Upload to Dropbox and check for response
            $dropbox_input = new ArrayInput($arguments);
            if ($upload->run($dropbox_input, $output) === 0) {
                $output->writeln('> <info>Sent to Dropbox</info>');
            } else {
                $output->writeln('> <error>Could not send to Dropbox</error>');
            }

            exec(sprintf('rm -f %s', $backup_file_path));
        }

        exec(sprintf('rm -rf %s', $backup_directory));
    }
}
