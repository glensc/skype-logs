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
     * @param string $databasePath
     */
    public function __construct($databasePath)
    {
        $this->validatePath($databasePath);
        $this->databasePath = $databasePath;
    }

    /**
     * @param string $user
     * @return \SQLite3Result
     */
    public function logsByUser($user)
    {
        return $this->connection()->query(
            "SELECT author, timestamp, body_xml, from_dispname FROM messages WHERE dialog_partner = '$user'"
        )->fetchAll();
    }

    /**
     * Return statement for iterating over chats
     *
     * @param int $limit limit number of rows returned
     * @return \PDOStatement
     */
    public function listChats($limit = null)
    {
        $sql = "
        SELECT chatname, c.topic, c.participants, min(m.timestamp) min_ts, max(m.timestamp) max_ts, count(*) c FROM Messages m
          LEFT JOIN Chats c ON c.name=m.chatname
        GROUP BY chatname
        ORDER BY c DESC
        ";

        if ($limit > 0) {
            $sql .= " LIMIT ".(int)$limit;
        }

        return $this->query($sql);
    }

    /**
     * Return messages for $chatname
     *
     * @param string $chatname name of Skype chat
     * @return \PDOStatement
     */
    public function getMessagesByChat($chatname)
    {
        $sql = "
            SELECT timestamp, chatname, author, body_xml
            FROM messages
            WHERE
            chatname=? AND body_xml IS NOT NULL
            ORDER BY timestamp ASC;
           ";

        return $this->query($sql, array($chatname));
    }

    /**
     * Run query, return statement object
     *
     * @param string $sql
     * @param array|null $parameters
     * @return \PDOStatement
     */
    public function query($sql, $parameters = null)
    {
        $st = $this->connection()->prepare($sql);
        $st->execute($parameters);

        return $st;
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