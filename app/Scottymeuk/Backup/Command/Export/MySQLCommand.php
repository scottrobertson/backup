<?php
namespace Scottymeuk\Backup\Command\Export;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput as ArrayInput;


class MySQLCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('export:mysql')
            ->setDescription('Backup all MySQL databases')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Exporting MySQL');
        $output->writeln('---------------');
        $output->writeln('');

        $config = $this->getApplication()->config;

        putenv('MYSQL_PWD=' . $config['mysql']['password']);

        $pdo = new \PDO('mysql:host=' . $config['mysql']['host'], $config['mysql']['username'], $config['mysql']['password']);
        $databases = $pdo->query('SHOW DATABASES');

        $ignored_databases = array(
            'performance_schema',
            'mysql',
            'test',
            'information_schema'
        );

        $upload = $this->getApplication()->find('dropbox:upload');

        while (($db = $databases->fetchColumn(0)) !== false) {
            if (in_array($db, $ignored_databases)) {
                continue;
            }

            $output->writeln($db);

            $root_path = 'mysql/' . date('Y-m-d');
            $path = $root_path . '/' . $db . '/';

            $local_path = ROOT . '/backups/' . $path;
            if (! is_dir($local_path)) {
                mkdir($local_path, 0777, true);
            }

            $file = $db . '.sql';
            $local_file = $local_path . $file;

            exec(sprintf('mysqldump -u%s %s > %s', $config['mysql']['username'], $db, $local_file), $shell_output, $response);
            if ($response != 0) {
                $output->writeln('> <error>Failed</error>');
            } else {
                $output->writeln('> <info>Exported</info>');

                $arguments = array(
                    'command' => 'dropbox:upload',
                    'file' => $local_file,
                    'dropbox_path'    => $config['dropbox']['path'] . $path . $file,
                );

                $input = new ArrayInput($arguments);
                $returnCode = $upload->run($input, $output);

                if ($returnCode === 0) {
                    $output->writeln('> <info>Sent to Dropbox</info>');
                } else {
                    $output->writeln('> <error>Could not send to Dropbox</error>');
                }

                exec('rm -rf ' . $root_path);
            }
            $output->writeln('');
        }
    }
}
