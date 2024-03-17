<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

final class FileAnalyzerTask
{
    private readonly string $dbPath;

    public function __construct(private readonly string $filePath)
    {
        $splitPath = explode("/", $this->filePath);
        $fileName = array_pop($splitPath);
        $this->dbPath = "{$fileName}-temp.db";
    }

    //should probably return an interface or something
    private function makeDb(): Database
    {
        return (new Database($this->dbPath))->createTables();
    }

    /** @return array{result: mixed, summaryDb: string} */
    public function run()
    {
        $header = null;
        $db = $this->makeDb();

        /** @var array{records: int, columns: array<mixed, array{appearances: int, values: array<mixed, int>}>} */
        $result = ["records" => 0, "columns" => []];

        $handle = fopen($this->filePath, "r");
        if(!$handle) {
            throw new \Exception("File not found");
        }

        while(($data = fgetcsv($handle)) !== false) {
            if(!$header) {
                $header = $data;
                continue;
            }
            $result["records"]++;
            foreach($header as $colNum => $colName) {
                $cell = $data[$colNum] ?? null;
                if(!$cell) {
                    continue;
                }
                if(!array_key_exists($colName, $result["columns"])) {
                    $result["columns"][$colName] = ["appearances" => 1, "values" => [$cell => 1]];
                    continue;
                }

                $result["columns"][$colName]["appearances"]++;

                //too much memory usage
                if(!array_key_exists($cell, $result["columns"][$colName]["values"])) {
                    $result["columns"][$colName]["values"][$cell] = 1;
                    continue;
                }
                $result["columns"][$colName]["values"][$cell]++;

                $valCount = count($result["columns"][$colName]["values"]);
                if($valCount > 500) {
                    $db->updateColumn($colName, $result["columns"][$colName]["values"]);
                    $result["columns"][$colName]["values"] = [];
                }

            }

        }
        foreach($result["columns"] as $colName => $data) {
            $db->updateColumn($colName, $data["values"]);
            $data["values"] = [];
        }
        echo "PARSED FILE {$this->filePath}".PHP_EOL;
        return ["result" => $result, "summaryDb" => $this->dbPath];
    }
}
