<?php

namespace Municipio\SmokeTests\SplitFileByLinesIntoShards;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class SplitFileByLinesIntoShardsTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->emptyDir(__DIR__ . '/generatedTestFiles');

        if (!is_dir(__DIR__ . '/generatedTestFiles')) {
            mkdir(__DIR__ . '/generatedTestFiles', 0777, true);
        }
    }

    protected function tearDown(): void {
        $this->emptyDir(__DIR__ . '/generatedTestFiles');
        parent::tearDown();
    }

    #[TestDox('splits file into multiple files based on number of wanted shards')]
    public function testSplitFileIntoShards(): void {
        $filePath = __DIR__ . '/generatedTestFiles/test_file.txt';
        file_put_contents($filePath, "Line 1\nLine 2\nLine 3\nLine ");

        $splitter = new \Municipio\SmokeTests\SplitFileByLinesIntoShards\SplitFileByLinesIntoShards($filePath, 1);
        $splitter->split();

        $this->assertFileExists(__DIR__ . '/generatedTestFiles/test_file-shard-1.txt');
        $this->assertFileExists(__DIR__ . '/generatedTestFiles/test_file-shard-2.txt');
        $this->assertFileExists(__DIR__ . '/generatedTestFiles/test_file-shard-3.txt');
        

        $shardFile1 = file_get_contents(__DIR__ . '/generatedTestFiles/test_file-shard-1.txt');
        $shardFile2 = file_get_contents(__DIR__ . '/generatedTestFiles/test_file-shard-2.txt');
        $shardFile3 = file_get_contents(__DIR__ . '/generatedTestFiles/test_file-shard-3.txt');

        // Assert file 1 to contain first half of lines
        $this->assertEquals("Line 1", $shardFile1);
        $this->assertEquals("Line 2", $shardFile2);
        $this->assertEquals("Line 3", $shardFile3);
    }

    #[TestDox('if number of lines is not divisible by number of shards, last shard should contain remaining lines')]
    public function testSplitFileIntoShardsWithRemainder(): void {
        $filePath = __DIR__ . '/generatedTestFiles/test_file_remainder.txt';
        file_put_contents($filePath, "Line 1\nLine 2\nLine 3");

        $splitter = new \Municipio\SmokeTests\SplitFileByLinesIntoShards\SplitFileByLinesIntoShards($filePath, 2);
        $splitter->split();

        $shardFile1 = file_get_contents(__DIR__ . '/generatedTestFiles/test_file_remainder-shard-1.txt');
        $shardFile2 = file_get_contents(__DIR__ . '/generatedTestFiles/test_file_remainder-shard-2.txt');

        // Assert file 1 to contain first half of lines
        $this->assertEquals("Line 1\nLine 2", $shardFile1, 'First shard should contain the first half of the lines.');
        $this->assertEquals("Line 3", $shardFile2, 'Second shard should contain the remaining lines.');
    }

    private function emptyDir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob("$dir/*") as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}