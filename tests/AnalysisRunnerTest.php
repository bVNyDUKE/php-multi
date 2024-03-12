<?php

declare(strict_types=1);

namespace Kpanda\PhpCsvAnalyzer;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertEquals;

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
        $res = $fa->execute();

        assertEquals(2, count($res));
    }

    public function testBigFiles(): void
    {
        $fa = new AnalysisRunner([
                "./tests/fixtures/deletions.csv-00019-of-00020",
                "./tests/fixtures/deletions.csv-00000-of-00020",
                "./tests/fixtures/deletions.csv-00016-of-00020",
                "./tests/fixtures/deletions.csv-00001-of-00020",
                "./tests/fixtures/deletions.csv-00014-of-00020",
                "./tests/fixtures/deletions.csv-00007-of-00020",
                "./tests/fixtures/deletions.csv-00013-of-00020",
                "./tests/fixtures/deletions.csv-00017-of-00020",
                "./tests/fixtures/deletions.csv-00008-of-00020",
                "./tests/fixtures/deletions.csv-00011-of-00020",
                "./tests/fixtures/deletions.csv-00002-of-00020",
                "./tests/fixtures/deletions.csv-00015-of-00020",
                "./tests/fixtures/deletions.csv-00009-of-00020",
                "./tests/fixtures/deletions.csv-00006-of-00020",
                "./tests/fixtures/deletions.csv-00012-of-00020",
                "./tests/fixtures/deletions.csv-00003-of-00020",
                "./tests/fixtures/deletions.csv-00004-of-00020",
                "./tests/fixtures/deletions.csv-00010-of-00020",
                "./tests/fixtures/deletions.csv-00018-of-00020",
                "./tests/fixtures/deletions.csv-00005-of-00020",
        ]);
        $res = $fa->execute();

        assertEquals(20, count($res));
    }

}
