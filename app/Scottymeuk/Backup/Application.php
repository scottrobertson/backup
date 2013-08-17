<?php
namespace Scottymeuk\Backup;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public $config = array();

    public function __construct($name, $version)
    {
        parent::__construct($name, $version);

        if (! file_exists(CONFIG)) {
            echo CONFIG . " file does not exist.\n";
            exit;
        }

        $this->config = json_decode(file_get_contents(CONFIG), true);
        if (! $this->config) {
            echo "Config file is invalid. \n";
            exit;
        }

        if (! isset($this->config['dropbox']) || ! isset($this->config['dropbox']['key']) || ! isset($this->config['dropbox']['secret'])) {
            echo "Dropbox config does not exist. \n";
            exit;
        }

        if (! isset($this->config['host'])) {
            echo 'You must specify a "host" in ' . CONFIG . "\n";
            exit;
        }

        $this->config['dropbox']['path'] = '/' . $this->config['host'] . '/';

    }

    public function writeConfig($config)
    {
        $this->config = $config;

        $json_options = 0;
        if (defined('JSON_PRETTY_PRINT')) {
            $json_options |= JSON_PRETTY_PRINT;
        }

        $json_config = json_encode($config, $json_options);
        file_put_contents(CONFIG, $json_config);
    }
}
