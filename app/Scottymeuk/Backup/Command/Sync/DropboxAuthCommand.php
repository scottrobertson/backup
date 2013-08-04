<?php
namespace Scottymeuk\Backup\Command\Sync;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DropboxAuthCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dropbox:auth')
            ->setDescription('Authorise this app to access your Dropbox account.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $config = $this->getApplication()->config;

        $appInfo = \Dropbox\AppInfo::loadFromJson($config['dropbox']);
        $webAuth = new \Dropbox\WebAuthNoRedirect($appInfo, 'scottymeuk-authorize', 'en');
        $authorizeUrl = $webAuth->start();

        $output->writeln('1. Go to: ' . $authorizeUrl);
        $output->writeln('2. Click "Allow"');
        $output->writeln('3. Copy the authorization code.');
        $output->writeln('');

        $authCode = $dialog->ask(
            $output,
            'Enter the authorization code here: '
        );

        $output->writeln('');

        list($accessToken, $userId) = $webAuth->finish($authCode);

        $output->writeln('Authorization complete.');
        $output->writeln('- User ID: ' . $userId);
        $output->writeln('- Access Token: ' . $accessToken);

        $authArr = array(
            'access_token' => $accessToken,
            'userId' => $userId
        );

        $config['dropbox'] = array_merge($config['dropbox'], $authArr);
        $this->getApplication()->writeConfig($config);
    }
}
