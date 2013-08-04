<?php
namespace Scottymeuk\Backup\Command;

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
            ->setName('backup:mysql')
            ->setDescription('Backup all MySQL databases')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getApplication()->config;

        putenv('MYSQL_PWD=' . $config['mysql']['password']);

        $dbh = new \PDO('mysql:host=' . $config['mysql']['host'], $config['mysql']['username'], $config['mysql']['password']);
        $dbs = $dbh->query('SHOW DATABASES');

        $ignored_dbs = array(
            'performance_schema',
            'mysql',
            'test',
            'information_schema'
        );

        $upload = $this->getApplication()->find('dropbox:upload');

        while (($db = $dbs->fetchColumn(0)) !== false) {
            if (in_array($db, $ignored_dbs)) {
                $output->writeln('<comment>Skipped:</comment> ' . $db);
                continue;
            }

            $path = 'mysql/' . date('ymd') . '/' . $db . '/';

            $local_path = ROOT . '/backups/' . $path;
            if (! is_dir($local_path)) {
                mkdir($local_path, 0777, true);
            }

            $file = $db . '.sql';
            $local_file = $local_path . $file;

            exec(sprintf('mysqldump -u%s %s > %s', $config['mysql']['username'], $db, $local_file), $shell_output, $response);
            if ($response != 0) {
                $output->writeln('<error>Failed:</error> ' . $db);
            } else {

                $arguments = array(
                    'command' => 'dropbox:upload',
                    'file' => $local_file,
                    'dropbox_path'    => $config['dropbox']['path'] . $path . $file,
                );

                $input = new ArrayInput($arguments);
                $returnCode = $upload->run($input, $output);

                if ($returnCode === 0) {
                    $output->writeln('<info>Success:</info> ' . $db);
                } else {
                    $output->writeln('<error>Failed:</error> ' . $db);
                }

                exec('rm -rf ' . $local_path);

            }
        }
    }
}
