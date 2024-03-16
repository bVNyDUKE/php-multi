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

        return $responses;
    }
}
