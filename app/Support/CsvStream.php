<?php

namespace App\Support;

use Generator;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvStream
{
    /**
     * Delimiters probed when reading files (Indonesian Excel often uses ";"
     * as the list separator, standard CSV uses ",").
     *
     * @var array<int, string>
     */
    private const DELIMITERS = [',', ';', "\t"];

    /**
     * Stream a CSV download. Emits a UTF-8 BOM so Excel renders Indonesian
     * text correctly, and neutralises spreadsheet formula injection.
     *
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int|string, mixed>>  $rows
     */
    public static function export(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Tidak dapat menulis output CSV.');
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(self::escapeCell(...), array_values($row)));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Read a CSV file into an array of rows keyed by a normalised header row.
     *
     * @return array<int, array<string, string|null>>
     */
    public static function read(string $path): array
    {
        return iterator_to_array(self::rows($path), false);
    }

    /**
     * Stream associative rows from a CSV file. Skips blank lines and tolerates
     * rows shorter than the header (missing cells become null).
     *
     * @return Generator<int, array<string, string|null>>
     */
    public static function rows(string $path): Generator
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Tidak dapat membuka file CSV: {$path}");
        }

        try {
            $line = fgets($handle);

            if ($line === false) {
                return;
            }

            $delimiter = self::detectDelimiter($line);

            $header = self::parseLine($line, $delimiter);

            if ($header === []) {
                return;
            }

            $keys = array_map(
                static fn (string $label): string => Str::of($label)->lower()->replace(' ', '_')->replaceMatches('/[^a-z0-9_]/', '')->toString(),
                $header,
            );

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($row === [null] || array_filter($row, static fn ($cell): bool => trim((string) $cell) !== '') === []) {
                    continue;
                }

                $assoc = [];

                foreach ($keys as $index => $key) {
                    $assoc[$key] = $index < count($row) ? trim((string) $row[$index]) : null;
                }

                yield $assoc;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Pick the delimiter the first line is most likely to use.
     */
    private static function detectDelimiter(string $line): string
    {
        $best = self::DELIMITERS[0];
        $bestCount = -1;

        foreach (self::DELIMITERS as $delimiter) {
            $count = substr_count($line, $delimiter);

            if ($count > $bestCount) {
                $best = $delimiter;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /**
     * Split a line into cells, stripping any leading UTF-8 BOM.
     *
     * @return array<int, string>
     */
    private static function parseLine(string $line, string $delimiter): array
    {
        $line = ltrim($line, "\xEF\xBB\xBF");

        return str_getcsv($line, $delimiter);
    }

    /**
     * Neutralise cells that start with a spreadsheet formula character so the
     * export cannot be used for CSV formula injection.
     */
    private static function escapeCell(mixed $value): string
    {
        $cell = (string) $value;

        if ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t"], true)) {
            return "'".$cell;
        }

        return $cell;
    }
}
