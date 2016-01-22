<?php

namespace Acme\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Acme\SkypeDatabase;

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
            ->setDescription("Lists Skype chats");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $skypeDB = new SkypeDatabase(SkypeDatabase::constructPath('glen'));

        $limit = $input->getOption('limit');
        $result = $skypeDB->listChats($limit);

        $table = new Table($output);
        $table->setHeaders(array('#', 'Chatname', 'Chat Title', 'Members', 'Messages'));
        $i = 1;
        foreach ($result as $row) {
            list($chatname, $title, $members, $messages) = array_values($row);
            $nmembers = count(explode(' ', $members));
            $table->addRow(array($i++, $chatname, $this->trim($title), $nmembers, $messages));
        }
        $table->render();
    }

    private function trim($s, $length = 60)
    {
        return mb_substr($s, 0, $length);
    }
}