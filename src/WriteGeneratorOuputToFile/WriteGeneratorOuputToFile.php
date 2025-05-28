<?php

namespace Municipio\SmokeTests\WriteGeneratorOuputToFile;

class WriteGeneratorOuputToFile
{
    /**
     * Writes the output of a generator to a file.
     *
     * @param \Generator $generator The generator to write to the file.
     * @param string $filePath The path to the file where the output will be written.
     */
    public static function write(\Generator $generator, string $filePath): void
    {
        $fileHandle = fopen($filePath, 'w');
        
        if ($fileHandle === false) {
            throw new \RuntimeException("Could not open file for writing: $filePath");
        }

        $lines = iterator_to_array($generator, false);
        $lastIndex = count($lines) - 1;
        foreach ($lines as $index => $line) {

            if( empty($line) ) {
                continue; // Skip empty lines
            }

            fwrite($fileHandle, $line);
            if ($index !== $lastIndex) {
                fwrite($fileHandle, PHP_EOL);
            }
        }

        fclose($fileHandle);
    }
}