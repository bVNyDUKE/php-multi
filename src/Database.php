<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Exception;
use PDO;

final class Database
{
    private PDO $db;

    public function __construct(public readonly string $dbName)
    {
        $this->db = new PDO("sqlite:{$this->dbName}");
    }

    public function createTables(): self
    {
        $this->db->exec('PRAGMA journal_mode = wal');
        $this->db->exec('PRAGMA synchronous = off');
        $this->db->exec('PRAGMA busy_timeout = 4000');
        $this->db->exec('CREATE TABLE column_values (column STRING, value STRING, count INTEGER)');
        return $this;
    }

    public function cleanUpTable(): void
    {
        unlink($this->dbName);
        unlink("{$this->dbName}-shm");
        unlink("{$this->dbName}-wal");
    }

    /**
     * @param array<string|int, int> $columnValues
     * @param string|int $column
     */
    public function updateColumn(string|int $column, array $columnValues): void
    {
        $stmt = $this->db->prepare('INSERT INTO column_values (column, value, count) VALUES (:col, :val, :count);');
        if(!$stmt) {
            [$msg, $code] = $this->db->errorInfo();
            throw new Exception("Prepare statement failed {$msg}", $code);
        }
        foreach($columnValues as $val => $count) {
            echo "UPDATING COLUMN TABLES {$column}, {$val}, {$count}" . PHP_EOL;
            $ok = $stmt->execute([
                "col" => $column,
                "val" => $val,
                "count" => $count,
            ]);
            if(!$ok) {
                [$msg, $code] = $this->db->errorInfo();
                throw new Exception("Insert statement failed {$msg}", $code);
            }
        }
    }

    /** @return array<string, int|string> */
    public function getSummary(): array
    {
        $res = $this->db->query('SELECT * FROM column_values GROUP BY column, value, count;');
        if(!$res) {
            [$msg, $code] = $this->db->errorInfo();
            throw new Exception("Error fetching summary {$msg}", $code);
        }
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }
}
