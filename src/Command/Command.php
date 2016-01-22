<?php

namespace Acme\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Acme\SkypeDatabase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /** @var SkypeDatabase */
    private $skypeDb;

    /**
     * Initializes the command just after the input has been validated.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Get Skype database Instance
     *
     * @return SkypeDatabase
     */
    protected function getSkypeDb()
    {
        if (!$this->skypeDb) {
            $db_path = $this->input->getOption('db-path');
            if (!$db_path) {
                // try to figure out db path
                $home = $_SERVER['HOME'];
                $paths = array(
                    // OSX
                    $home."/Library/Application Support/Skype/*/main.db",
                    // Linux, FHS
                    $home."/.config/Skype/*/main.db",
                    // Linux, Standard
                    $home."/.Skype/*/main.db",

                );

                foreach ($paths as $glob) {
                    foreach (glob($glob) as $file) {
                        $db_path = $file;
                        break;
                    }
                }

                if (!$db_path) {
                    throw new \InvalidArgumentException('Unable to locate Skype database, try --db-path option');
                }
            }

            $this->skypeDb = new SkypeDatabase($db_path);
        }

        return $this->skypeDb;
    }

    /**
     * Truncate string at $length characters
     *
     * @param string $s
     * @param int $length
     * @return string
     */
    protected function trim($s, $length = 60)
    {
        return mb_substr($s, 0, $length);
    }

    /**
     * Format UNIX timestamp into readable date
     *
     * @param int $ts
     * @return string
     */
    protected function formatTime($ts)
    {
        return strftime("%Y-%m-%d %H:%M:%S", $ts);
    }
}