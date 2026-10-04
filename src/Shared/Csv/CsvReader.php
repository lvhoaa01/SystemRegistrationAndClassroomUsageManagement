<?php

declare(strict_types=1);

namespace App\Shared\Csv;

use Generator;

final class CsvReader
{
    public function rows(string $path): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('CSV_OPEN_FAILED');
        }

        try {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                yield $row;
            }
        } finally {
            fclose($handle);
        }
    }
}

