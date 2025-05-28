<?php

namespace Municipio\SmokeTests\WriteGeneratorOuputToFile;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class WriteGeneratorOuputToFileTest extends TestCase
{
    #[TestDox('writes generator output to file')]
    public function testCanBeInstantiated(): void
    {
        $filePath = __DIR__ . '/test_output.txt';
        $generator = (function () {
            yield 'Line 1';
            yield 'Line 2';
            yield 'Line 3';
        })();

        WriteGeneratorOuputToFile::write($generator, $filePath);

        $this->assertFileExists($filePath, 'Output file should exist after writing.');
        $this->assertStringEqualsFile($filePath, "Line 1\nLine 2\nLine 3", 'Output file content should match the generator output.');

        unlink($filePath); // Clean up
    }

    #[TestDox('contains no empty lines, not even at the end')]
    public function testNoEmptyLinesAtEndOfOutput(): void
    {
        $filePath = __DIR__ . '/test_output.txt';
        $generator = (function () {
            yield 'Line 1';
            yield 'Line 2';
            yield '';
            yield 'Line 3';
        })();

        WriteGeneratorOuputToFile::write($generator, $filePath);

        $content = file_get_contents($filePath);
        $this->assertStringNotContainsString("\n\n", $content, 'Output file should not contain empty lines.');
        $this->assertStringEndsNotWith("\n", $content, 'Output file should not end with an empty line.');

        unlink($filePath); // Clean up
    }
}