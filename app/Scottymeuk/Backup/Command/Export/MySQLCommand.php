<?php
namespace Scottymeuk\Backup\Command\Export;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput as ArrayInput;

class MySQLCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('export:mysql')
            ->setDescription('Backup all MySQL databases');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Exporting MySQL');
        $output->writeln('---------------');
        $output->writeln('');

        $config = $this->getApplication()->config;
        if (! isset($config['mysql'])) {
            $output->writeln('<error>No MySQL config found.</error>');

            return 1;
        }

        // Set the MySQL password as an enviroment variable.
        // The reason for this is that mysql now throws a warning
        // that using passwords in CLI is insecure
        putenv('MYSQL_PWD=' . $config['mysql']['password']);

        // Get a list of all the databases this user has access to
        $pdo = new \PDO('mysql:host=' . $config['mysql']['host'], $config['mysql']['username'], $config['mysql']['password']);
        $databases = $pdo->query('SHOW DATABASES');

        // A list of all the databases we wish to ignore.
        // TODO: allow this to be configured in config.json
        $ignored_databases = array(
            'performance_schema',
            'mysql',
            'test',
            'information_schema'
        );

        // The root path as to which the files shall be stored in Dropbox
        // and locally
        $root_path = 'mysql/' . date('Y-m-d');

        // Setup the Dropbox upload command
        $upload = $this->getApplication()->find('dropbox:upload');

        // Loop over each database
        while (($db = $databases->fetchColumn(0)) !== false) {

            // If the database is in our ignored list, then skip
            if (in_array($db, $ignored_databases)) {
                continue;
            }

            // Output which DB we are backing up
            $output->writeln('"'.$db.'"');

            // Setup the paths etc
            $path = $root_path . '/' . $db . '/';
            $local_path = BACKUPS . '/' . $path;
            $file = $db . '.sql.gz';
            $local_file = $local_path . $file;

            // Setup the Dropbox command with arguments
            $arguments = array(
                'command' => 'dropbox:upload',
                'file' => $local_file,
                'dropbox_path'    => $config['dropbox']['path'] . $path . $file,
            );
            $dropbox_input = new ArrayInput($arguments);

            if (! is_dir($local_path)) {
                mkdir($local_path, 0777, true);
            }

            $output->writeln('');
            $output->writeln('Exporting');
            // Export the MySQL database using "mysqldump"
            $possibleerrorstring='';
            // prepair parameters
            if (isset($config['mysql']['username']) && !empty($config['mysql']['username'])){
                $userstring=' -u%s';
            }else{
                $userstring='';
                $config['mysql']['username']='';
                $possibleerrorstring.="\r\n".' Username not set!';
            }
            if (isset($config['mysql']['host']) && !empty($config['mysql']['host'])){
                $hostdstring=' -h %s';
            }else{
                $hostdstring='';
                $config['mysql']['host']='';
                $possibleerrorstring.="\r\n".' Mysql server hostname not set!';
            }

            $execstring =sprintf(
                'mysqldump '.$userstring.$hostdstring.' %s | gzip > %s',
                $config['mysql']['username'],
                $config['mysql']['host'],
                $db,
                $local_file
            );
            exec($execstring, $shell_output, $response);

            // If the response code was not 0, then something went wrong
            if ($response != 0) {
                $output->writeln('> <error>Failed</error>');
                $output->writeln('> <error>Try to execute:'.$execstring.'</error>');
                $output->writeln('> <error>Possible errors:'.$possibleerrorstring.'</error>');
            } else {
                $output->writeln('> <info>Exported</info>');
                $upload->run($dropbox_input, $output);
            }

            // Finally, remove the local backup folder
            exec('rm -rf ' . $root_path);
            $output->writeln('');
        }
    }
}
