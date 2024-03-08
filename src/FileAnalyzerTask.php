<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Amp\Cancellation;
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

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {

        $header = [];
        $handle = fopen($this->filePath, "r");

        if(!$handle) {
            throw new \Exception("File not found");
        }

        /** @var array{records: int, columns: array<mixed, array{appearances: int, values: array<mixed, int>}>} */
        $result = ["records" => 0, "columns" => []];

        while(($data = fgetcsv($handle)) !== false) {
            if(count($header) === 0) {
                $header = $data;
                continue;
            }
            $result["records"]++;

            foreach($header as $colNum => $colName) {
                $cell = $data[$colNum];
                if(!$cell) {
                    continue;
                }
                if(!array_key_exists($colName, $result["columns"])) {
                    $result["columns"][$colName] = ["appearances" => 1, "values" => [$cell => 1]];
                    continue;
                }

                $result["columns"][$colName]["appearances"]++;

                if(!array_key_exists($cell, $result["columns"][$colName]["values"])) {
                    $result["columns"][$colName]["values"][$cell] = 1;
                    continue;
                }

                $result["columns"][$colName]["values"][$cell]++;
            }
        }

        fclose($handle);
        return $result;
    }
}
