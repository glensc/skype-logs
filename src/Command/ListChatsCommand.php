<?php

namespace Acme\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListChatsCommand extends Command
{
    const DEFAULT_LIMIT = 40;

    /**
     *
     */
    public function configure()
    {
        $this
            ->setName("list-chats")
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                "Set limit of items displayed, use -1 to show all",
                self::DEFAULT_LIMIT
            )
            ->addOption(
                'title',
                null,
                InputOption::VALUE_REQUIRED,
                "Match string part of chat title using glob match"
            )
            ->addOption(
                'db-path',
                null,
                InputOption::VALUE_REQUIRED,
                "Set path to Skype database, f.e /Library/Application Support/Skype/USERNAME/main.db"
            )
            ->setDescription("Lists Skype chats");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');
        $title = $input->getOption('title');
        $result = $this->getSkypeDb()->listChats($title, $limit);

        $table = new Table($output);
        $table->setHeaders(array('#', 'Chatname', 'Chat Title', 'Members', 'First Message', 'Last Message', 'Messages'));
        $i = 1;
        foreach ($result as $row) {
            $title = $this->trim($row['topic'] ?: $row['displayname']);
            $nmembers = $row['participants'] !== null ? count(explode(' ', $row['members'])) : '?';

            $table->addRow(
                array(
                    $i++,
                    $row['chatname'],
                    $title,
                    $nmembers,
                    $this->formatTime($row['min_ts']),
                    $this->formatTime($row['max_ts']),
                    $row['messages'],
                )
            );
        }
        $table->render();
    }
}