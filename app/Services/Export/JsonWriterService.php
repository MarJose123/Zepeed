<?php

namespace App\Services\Export;

use RuntimeException;

class JsonWriterService
{
    /**
     * Write rows to a JSON file and return the stored file path.
     *
     * @param iterable<array<string,mixed>> $rows
     * @param string                        $filename
     */
    public function write(iterable $rows, string $filename): string
    {
        $path = "exports/{$filename}.json";
        $fullPath = storage_path("app/private/{$path}");

        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($fullPath, 'w');

        if ($handle === false) {
            throw new RuntimeException("Unable to open file for writing: {$fullPath}");
        }

        fwrite($handle, '[');

        $first = true;

        foreach ($rows as $row) {
            if (! $first) {
                fwrite($handle, ',');
            }

            fwrite($handle, json_encode($row, JSON_THROW_ON_ERROR));
            $first = false;
        }

        fwrite($handle, ']');
        fclose($handle);

        return $path;
    }
}
