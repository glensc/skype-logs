<?php

namespace Acme;

use PDO;

class SkypeDatabase
{
    /**
     * Chat type constants
     *
     * @see https://github.com/suurjaak/Skyperious/blob/v3.5/skyperious/skypedata.py#L61-L83
     */
    // Changed chat topic or picture
    const MESSAGE_TYPE_TOPIC = 2;

    // Ordinary message
    const MESSAGE_TYPE_MESSAGE = 61;
    // File transfer
    const MESSAGE_TYPE_FILE = 68;
    // Started Skype call
    const MESSAGE_TYPE_CALL = 30;
    // Skype call ended
    const MESSAGE_TYPE_CALL_END = 39;

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
     * @param string $title Match string part of chat title
     * @param int $limit limit number of rows returned
     * @return \PDOStatement
     */
    public function listChats($title = null, $limit = null)
    {
        $params = array();
        $sql = "
        SELECT chatname,
          c.displayname,
          ch.topic, ch.participants,
          min(m.timestamp) min_ts, max(m.timestamp) max_ts,
          count(*) messages FROM Messages m
        LEFT JOIN Conversations c ON c.identity=m.chatname
        LEFT JOIN Chats ch ON ch.name=m.chatname
        ";


        if ($title) {
            $sql .= " WHERE ch.topic LIKE ? OR c.displayname LIKE ?";
            $like = '%'.str_replace('*', '%', $title).'%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= "
        GROUP BY chatname
        ORDER BY messages DESC
        ";

        if ($limit > 0) {
            $sql .= " LIMIT ".(int)$limit;
        }

        return $this->query($sql, $params);
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
            SELECT timestamp, type, chatname, author, body_xml
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