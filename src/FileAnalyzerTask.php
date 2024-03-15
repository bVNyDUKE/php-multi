<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;

/**
 * @implements Task<mixed, mixed, mixed>
 */
final class FileAnalyzerTask implements Task
{
    public function __construct(private readonly string $filePath)
    {
    }

    /** @return \Generator<array<int, int|string>> */
    private function recordIterator()
    {
        $handle = fopen($this->filePath, "r");
        if(!$handle) {
            throw new \Exception("File not found");
        }
        try {
            while(($data = fgetcsv($handle)) !== false) {
                yield $data;
            }
        } finally {
            fclose($handle);
        }
    }

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $header = null;
        $splitPath = explode("/", $this->filePath);
        $fileName = array_pop($splitPath);
        $db = new Database($fileName);

        /** @var array{records: int, columns: array<mixed, array{appearances: int, values: array<mixed, int>}>} */
        $result = ["records" => 0, "columns" => []];

        foreach($this->recordIterator() as $data) {
            if($cancellation->isRequested()) {
                throw new CancelledException();
            }
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
                if($valCount > 1_000) {
                    $db->updateColumn($colName, $result["columns"][$colName]["values"]);
                    $result["columns"][$colName]["values"] = [];
                }

            }
        }
        echo "PARSED FILE {$this->filePath}".PHP_EOL;
        var_dump($result);
        return $result;
    }
}
