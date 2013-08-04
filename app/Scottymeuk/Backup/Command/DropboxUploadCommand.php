<?php
namespace Scottymeuk\Backup\Command;

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
            ->setDescription('Upload a file to Dropbox')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Which file do you want to upload?'
            )
            ->addArgument(
                'dropbox_path',
                InputArgument::REQUIRED,
                'The Dropbox path'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $config = $this->getApplication()->config;

        $file = $input->getArgument('file');
        $dropbox_path = $input->getArgument('dropbox_path');

        if (! file_exists($file)) {
            $output->writeln('<error>File does not exist.</error>');
            return 1;
        }

        $client = new \Dropbox\Client($config['dropbox']['access_token'], "scottymeuk-upload");

        $size = null;
        if (stream_is_local($file)) {
            $size = filesize($file);
        }

        $metadata = $client->uploadFile($dropbox_path, \Dropbox\WriteMode::force(), fopen($file, "rb"), $size);

        if ($metadata) {
            return 0;
        }

        return 1;
    }
}
