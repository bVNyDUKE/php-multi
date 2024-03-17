<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Exception;
use SQLite3;
use SQLite3Result;

final class Database
{
    private SQLite3 $db;

    public function __construct(private string $dbName)
    {
        $this->db = new SQLite3($this->dbName);
    }

    public function createTables(): self
    {
        $this->db->exec('PRAGMA journal_mode = wal');
        $this->db->exec('PRAGMA synchronous = off');
        $this->db->exec('PRAGMA busy_timeout = 4000');
        $this->db->exec('CREATE TABLE column_values (column STRING, value STRING, count INTEGER)');
        return $this;
    }

    public function __destruct()
    {
        $this->db->close();
    }

    /**
     * @param array<string|int, int> $columnValues
     * @param string|int $column
     */
    public function updateColumn(string|int $column, array $columnValues): void
    {
        $stmt = $this->db->prepare('INSERT INTO column_values (column, value, count) VALUES (:col, :val, :count);');
        if(!$stmt) {
            throw new Exception(
                "Prepare statement failed {$this->db->lastErrorMsg()}",
                $this->db->lastErrorCode()
            );
        }
        foreach($columnValues as $val => $count) {
            echo "UPDATING COLUMN TABLES {$column}, {$val}, {$count}" . PHP_EOL;
            $stmt->bindValue(":col", $column, SQLITE3_TEXT);
            $stmt->bindValue(":val", $val, SQLITE3_TEXT);
            $stmt->bindValue(":count", $count, SQLITE3_INTEGER);
            $res = $stmt->execute();
            if(!$res) {
                throw new Exception(
                    "Insert statement failed {$this->db->lastErrorMsg()}",
                    $this->db->lastErrorCode()
                );
            }
            $stmt->clear();
            $stmt->reset();
        }
    }

    public function getSummary(): SQLite3Result
    {
        $res = $this->db->query('SELECT * FROM column_values GROUP BY column, value, count;');
        if(!$res) {
            throw new Exception(
                "Error fetching summary {$this->db->lastErrorMsg()}",
                $this->db->lastErrorCode()
            );
        }
        return $res;
    }
}
