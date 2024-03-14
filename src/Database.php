<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Exception;
use SQLite3;

final class Database
{
    private string $path = __DIR__."/tmp.db";
    private SQLite3 $db;

    private function __construct()
    {
        $this->db = new SQLite3($this->path);
        $this->db->exec('PRAGMA journal_mode = wal');
        $this->db->exec('PRAGMA synchronous = off');
        $this->db->exec('PRAGMA busy_timeout = 4000');
    }

    private function createTable(): void
    {
        $this->db->exec('CREATE TABLE column_values (column STRING, value STRING, count INTEGER)');
    }

    public static function createAndConnect(): Database
    {
        $db = new static();
        $db->createTable();
        return $db;
    }

    public static function connect(): Database
    {
        return new static();
    }

    /**
     * @param array<string|int, int> $columnValues
     * @param string|int $column
     */
    public function updateColumn(string|int $column, array $columnValues): void
    {
        $stmt = $this->db->prepare('INSERT INTO column_values (column, value, count) VALUES (:col, :val, :count)');
        if(!$stmt) {
            throw new Exception(
                "Prepare statement failed {$this->db->lastErrorMsg()}",
                $this->db->lastErrorCode()
            );
        }
        $stmt->bindParam(":col", $column);
        foreach($columnValues as $val => $count) {
            $stmt->bindParam(":val", $val, SQLITE3_TEXT);
            $stmt->bindParam(":count", $count, SQLITE3_INTEGER);
            $res = $stmt->execute();
            if(!$res) {
                throw new Exception(
                    "Insert statement failed {$this->db->lastErrorMsg()}",
                    $this->db->lastErrorCode()
                );
            }
        }
        $stmt->close();
    }

    public function printSummary(): void
    {
        $res = $this->db->query('SELECT * FROM column_values GROUP BY column, value');
        if(!$res) {
            throw new Exception(
                "Error fetching summary {$this->db->lastErrorMsg()}",
                $this->db->lastErrorCode()
            );
        }
        var_dump($res);
    }

    public function cleanUp(): void
    {
        unlink($this->path);
    }
}
