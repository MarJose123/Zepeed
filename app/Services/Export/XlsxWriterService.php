<?php

namespace App\Services\Export;

use Spatie\SimpleExcel\SimpleExcelWriter;

final class XlsxWriterService
{
    /**
     * Write rows to an XLSX file on the local private disk and return the
     * relative path (relative to storage/app/private/).
     *
     * @param iterable<array<string, mixed>> $rows
     * @param list<string>                   $headers
     * @param string                         $filename bare name, no extension
     */
    public function write(iterable $rows, array $headers, string $filename): string
    {
        $path = "exports/{$filename}.xlsx";
        $fullPath = storage_path("app/private/{$path}");

        $this->ensureDirectory($fullPath);

        $writer = SimpleExcelWriter::create($fullPath);

        foreach ($rows as $row) {
            $writer->addRow(array_combine($headers, array_values($row)));
        }

        $writer->close();

        return $path;
    }

    private function ensureDirectory(string $fullPath): void
    {
        $dir = dirname($fullPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
