<?php
namespace Scottymeuk\Backup\Command\Sync;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DropboxUploadCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dropbox:upload')
            ->setDescription('Upload a file to Dropbox using their API')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'What file do you want to upload?'
            )
            ->addArgument(
                'dropbox_path',
                InputArgument::REQUIRED,
                'What should the file be stored as in Dropbox?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Uploading to Dropbox');

        $dialog = $this->getHelperSet()->get('dialog');
        $config = $this->getApplication()->config;

        $file = $input->getArgument('file');
        $dropbox_path = $input->getArgument('dropbox_path');

        if (! file_exists($file)) {
            $output->writeln('><error>File does not exist.</error>');
            return 1;
        }

        $client = new \Dropbox\Client(
            $config['dropbox']['access_token'],
            'scottymeuk-upload'
        );

        $metadata = $client->getMetaData($dropbox_path);
        if (isset($metadata['bytes']) && ($metadata['bytes'] === filesize($file))) {
            $output->writeln('><info> File size the same, skipping</info>');
            return 0;
        }

        $upload = $client->uploadFile(
            $dropbox_path,
            \Dropbox\WriteMode::force(),
            fopen($file, "rb"),
            filesize($file)
        );

        if ($upload) {
            $output->writeln('> <info>Done</info>');
            return 0;
        }

        $output->writeln('> <error>Failed sending to Dropbox</error>');
        return 1;
    }
}
