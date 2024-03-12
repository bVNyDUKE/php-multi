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

    /** @return array<array-key, mixed> */
    public function execute()
    {
        var_dump(opcache_get_status());
        $execs = [];
        foreach($this->filePaths as $path) {
            $execs[$path] = Worker\submit(new FileAnalyzerTask($path));
        }

        $responses = Future\await(array_map(fn (Worker\Execution $e) => $e->getFuture(), $execs));

        return $responses;
    }
}
