<?php

namespace Acme;

use PDO;

class SkypeDatabase
{
    /**
     * @var string
     */
    private $databasePath;

    /**
     * @param $databasePath
     */
    public function __construct($databasePath)
    {
        $this->databasePath = $databasePath;
        $this->validatePath($this->databasePath);
    }

    /**
     * @param $username
     * @return string
     */
    public static function constructPath($username)
    {
        return $_SERVER['HOME']. "/Library/Application Support/Skype/$username/main.db";
    }

    /**
     * @param string $user
     * @return \SQLite3Result
     */
    public function logsByUser($user)
    {
        return $this->connection()->query("SELECT author, timestamp, body_xml, from_dispname FROM messages WHERE dialog_partner = '$user'")->fetchAll();
    }

    public function query($sql, $parameters)
    {
        $parameters = is_array($parameters) ? $parameters : [$parameters];
        return $this->connection()->prepare($sql)->execute($parameters)->fetchAll();
    }

    /**
     * @return \PDO
     */
    private function connection()
    {
        $pdo = new PDO("sqlite:{$this->databasePath}");
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Validate if database could be accessed
     *
     * @param $databasePath
     */
    private function validatePath($databasePath)
    {
        if (!file_exists($databasePath)) {
            throw new \InvalidArgumentException("Skype Database path '{$databasePath}' is not valid");
        }

        if (!is_readable($databasePath)) {
            throw new \InvalidArgumentException("Skype Database path '{$databasePath}' is not readable");
        }
    }
}