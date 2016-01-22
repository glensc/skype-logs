<?php

namespace Acme\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;

class Command extends BaseCommand
{
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