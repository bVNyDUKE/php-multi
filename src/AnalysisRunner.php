<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Spatie\Fork\Fork;

final class AnalysisRunner
{
    /** @param string[] $filePaths */
    public function __construct(public array $filePaths)
    {
    }

    /** @return array<array-key, mixed> */
    public function execute()
    {
        $tasks = array_map(fn ($path) => fn () => (new FileAnalyzerTask($path))->run(), $this->filePaths);
        $responses = Fork::new()->run(...$tasks);

        foreach($responses as $r) {
            $db = new Database($r["summaryDb"]);
            $summary = $db->getSummary();
            var_dump($summary->fetchArray(SQLITE3_ASSOC));
        }

        return $responses;
    }
}
