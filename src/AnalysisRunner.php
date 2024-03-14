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
        $execs = [];
        $db = Database::createAndConnect();
        foreach($this->filePaths as $path) {
            $execs[$path] = Worker\submit(new FileAnalyzerTask($path));
        }

        $responses = Future\await(array_map(fn (Worker\Execution $e) => $e->getFuture(), $execs));

        $db->printSummary();
        $db->cleanUp();

        return $responses;
    }
}
