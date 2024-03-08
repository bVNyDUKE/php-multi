<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertTrue;

#[CoversClass(AnalysisRunner::class)]
final class AnalysisRunnerTest extends TestCase
{
    /** @var string[] */
    private array $testFiles = ["./sanity.csv", "./sanity2.csv"];

    private function makeCsv(string $fileName): void
    {

        $sanityCsv = [
            ["col1", "col2"],
            ["c1v1", "c2v1"],
            ["c1v2", "c2v1"],
            ["", "c2v2"],
        ];
        $fp = fopen($fileName, "w");
        if(!$fp) {
            throw new Exception("Error generating sanity csv file");
        }

        foreach($sanityCsv as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    public function setUp(): void
    {
        foreach($this->testFiles as $testFile) {
            $this->makeCsv($testFile);
        }
    }

    public function tearDown(): void
    {
        foreach($this->testFiles as $testFile) {
            $success = unlink($testFile);
            if(!$success) {
                throw new Exception("Error deleting sanity csv file");
            }
        }
    }

    public function testRunAnalysis(): void
    {
        $fa = new AnalysisRunner($this->testFiles);
        $fa->execute();

        assertTrue(true);
    }

}
