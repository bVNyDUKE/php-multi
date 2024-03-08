<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Amp\Future;
use Amp\Parallel\Worker;

final class AnalysisRunner
{
    /** @param string[] $filePaths */
    public function __construct(public array $filePaths)
    {
    }

    public function execute(): void
    {
        $execs = [];
        foreach($this->filePaths as $path) {
            $execs[$path] = Worker\submit(new FileAnalyzerTask($path));
        }

        $responses = Future\await(array_map(fn (Worker\Execution $e) => $e->getFuture(), $execs));

        foreach($responses as $file => $res) {
            echo "Read file: {$file}". PHP_EOL;
            var_dump($res);
        }
        echo "FINISHED" . PHP_EOL;
    }
}
