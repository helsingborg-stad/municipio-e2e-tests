<?php

namespace Municipio\SmokeTests\SplitFileByLinesIntoShards;

class SplitFileByLinesIntoShards {
    public function __construct(
        private string $filePath,
        private int $linesPerShard = 300
    ) {}

    public function split(): void
    {
        if ($this->linesPerShard < 1) {
            throw new \InvalidArgumentException('Lines per shard must be at least 1.');
        }

        $fileHandle = fopen($this->filePath, 'r');
        if (!$fileHandle) {
            throw new \RuntimeException("Could not open file: {$this->filePath}");
        }

        $shardNumber = 1;
        $currentLines = [];
        while (($line = fgets($fileHandle)) !== false) {
            $currentLines[] = trim($line);
            if (count($currentLines) === $this->linesPerShard) {
                $shardFilePath = str_replace('.txt', "-shard-{$shardNumber}.txt", $this->filePath);
                file_put_contents($shardFilePath, implode(PHP_EOL, $currentLines));
                $currentLines = [];
                $shardNumber++;
            }
        }
        // Write remaining lines to the last shard if any
        if (!empty($currentLines)) {
            $shardFilePath = str_replace('.txt', "-shard-{$shardNumber}.txt", $this->filePath);
            file_put_contents($shardFilePath, implode(PHP_EOL, $currentLines));
        }
        fclose($fileHandle);
    }
}
