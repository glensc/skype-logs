<?php

namespace Acme\Command;

use Acme\SkypeDatabase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportByChatCommand extends Command
{
    const DEFAULT_LIMIT = 100;

    /**
     *
     */
    public function configure()
    {
        $this->setName("export-chat")
            ->addArgument('chatname', InputArgument::REQUIRED, 'Name of chat to export')
            ->addOption(
                'destination',
                'd',
                InputOption::VALUE_OPTIONAL,
                "Destination folder for the output",
                'skype-log-<chatname>'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                "Set name of channel in output"
            )
            ->addOption(
                'usermap',
                null,
                InputOption::VALUE_REQUIRED,
                "username mapping"
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                "Set limit of items displayed, use -1 to show all",
                self::DEFAULT_LIMIT
            )
            ->addOption(
                'db-path',
                null,
                InputOption::VALUE_REQUIRED,
                "Set path to Skype database, f.e /Library/Application Support/Skype/USERNAME/main.db"
            )
            ->setDescription("Exports logs from a given Chat");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int null or 0 if everything went fine, or an error code
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $chatname = $input->getArgument('chatname');

        $output->writeln("<info>Exporting messages from {$chatname} chat</info>");
        $res = $this->getSkypeDb()->getMessagesByChat($chatname);

        $this->toCsv($this->filterResults($res));
    }

    /**
     * Process each result
     * This formats for specifically for Slack CSV import
     *
     * @link https://get.slack.help/hc/en-us/articles/201748703-Importing-message-history
     *
     * @param \Traversable $res
     * @return \Generator
     */
    private function filterResults($res)
    {
        $name = $this->input->getOption('name');
        $usermap = $this->getUsermap();
        // skip topic changes, file uploads
        $skip_types = array(
            SkypeDatabase::MESSAGE_TYPE_TOPIC,
            SkypeDatabase::MESSAGE_TYPE_FILE,
            SkypeDatabase::MESSAGE_TYPE_CALL,
            SkypeDatabase::MESSAGE_TYPE_CALL_END,
        );

        $types_seen = array();
        foreach ($res as $row) {
            if (!isset($types_seen[$row['type']])) {
                $types_seen[$row['type']] = 0;
            }
            $types_seen[$row['type']]++;

            $message = $this->formatMessage($row['body_xml']);
            if (in_array($row['type'], $skip_types)) {
                $this->output->writeln("Skipping message type {$row['type']}: {$message}");
                continue;
            }

            // skip system messages
            if ($row['author'] == 'sys') {
                $this->output->writeln("Skipping message type {$row['type']}: from {$row['author']}: {$message}");
                continue;
            }

            $value = array(
                $row['timestamp'],
                $name ?: $row['chatname'],
                isset($usermap[$row['author']]) ? $usermap[$row['author']] : $row['author'],
                $message,
            );
            yield $value;
        }
    }

    /**
     * Return username mapping
     *
     * @return array
     */
    private function getUsermap()
    {
        $filename = $this->input->getOption('usermap');
        if (!$filename) {
            return null;
        }

        if (!is_readable($filename)) {
            throw new \InvalidArgumentException("$filename is not readable");
        }
        $users = array();
        $lines = file($filename);
        foreach ($lines as $line) {
            list($source, $target) = explode(':', trim($line));
            $users[$source] = $target;
        }

        return $users;
    }

    /**
     * @param $data
     */
    private function toCsv($data)
    {
        $destination = $this->getDestination();
        $destination = $destination.'.csv';

        $fh = fopen($destination, "w+");
        foreach ($data as $row) {
            fputcsv($fh, $row, ',');
        }
        fclose($fh);

        $this->output->writeln("<info>Done, file generated at '{$destination}'</info>");
    }

    /**
     * Get output filename
     *
     * @return string
     */
    private function getDestination()
    {
        $destination = $this->input->getOption('destination');
        $destination = str_replace("<chatname>", $this->input->getArgument('chatname'), $destination);

        // replace unsafe chars
        $destination = strtr($destination, DIRECTORY_SEPARATOR, '_');

        return $destination;
    }

    /**
     * Format message body
     *
     * @param string $message
     * @return string
     */
    private function formatMessage($message)
    {
        //strip Skype tags (<ss type="dance">(dance)</ss>, ...)
        $message = strip_tags($message);

        // decode HTML("&gt;", "&apos;"-> ">", "'")
        $message = html_entity_decode($message, ENT_QUOTES | ENT_XML1, self::ENCODING);

        // if message ends with backslash, add space to prevent it breaking csv
        if ($message && $message[strlen($message) - 1] == '\\') {
            $message .= ' ';
        }

        return $message;
    }
}
