<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CsvImporter
{
    /**
     * Stream a CSV file through a per-row normaliser and upsert every valid
     * row, skipping invalid ones so one bad line never aborts the whole file.
     *
     * @param  class-string<Model>  $model
     * @param  callable(array<string, string|null>, int): array<string, mixed>  $normalize  throws InvalidArgumentException on invalid rows
     * @param  callable(array<string, mixed>): ?Model  $findExisting
     * @return array{created: int, updated: int, failed: int, errors: array<int, string>}
     */
    public static function run(string $path, string $model, callable $normalize, callable $findExisting): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach (CsvStream::rows($path) as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $data = $normalize($row, $rowNumber);
            } catch (InvalidArgumentException $exception) {
                $errors[] = "Baris {$rowNumber}: {$exception->getMessage()}";

                continue;
            }

            $existing = $findExisting($data);

            if ($existing === null) {
                $model::query()->create($data);

                $created++;
            } else {
                $existing->update($data);

                $updated++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Build a human-readable summary of an import result for a flash toast.
     *
     * @param  array{created: int, updated: int, failed: int, errors: array<int, string>}  $result
     */
    public static function summaryMessage(array $result): string
    {
        $message = __('Import selesai: :created dibuat, :updated diperbarui, :failed gagal.', [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'failed' => $result['failed'],
        ]);

        if ($result['failed'] > 0) {
            $message .= ' '.implode(' | ', array_slice($result['errors'], 0, 3));
        }

        return $message;
    }

    /**
     * Trim a cell into a value or null when blank.
     */
    public static function nullableCell(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Parse a cell into a non-negative decimal, accepting Indonesian number
     * formats ("10.500,75", "Rp 25.000") as well as plain floats.
     */
    public static function parseDecimal(mixed $value, string $label): float
    {
        $raw = self::normalizeNumber($value);

        if (! is_numeric($raw) || (float) $raw < 0) {
            throw new InvalidArgumentException("{$label} tidak valid.");
        }

        return (float) $raw;
    }

    /**
     * Parse a cell into a non-negative whole number.
     */
    public static function parseInteger(mixed $value, string $label): int
    {
        return (int) round(self::parseDecimal($value, $label));
    }

    /**
     * Normalise an Indonesian/plain number string to a dot-decimal string.
     */
    private static function normalizeNumber(mixed $value): string
    {
        $raw = trim((string) $value);
        $raw = preg_replace('/\s+/u', '', $raw) ?? '';
        $raw = str_ireplace(['Rp', 'rp'], '', $raw);

        if ($raw === '') {
            return '0';
        }

        $hasDot = str_contains($raw, '.');
        $hasComma = str_contains($raw, ',');

        if ($hasDot && $hasComma) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif ($hasComma) {
            $raw = str_replace(',', '.', $raw);
        } elseif ($hasDot) {
            [, $decimal] = array_pad(explode('.', $raw), 2, '');

            if ($decimal !== '' && strlen($decimal) === 3) {
                $raw = str_replace('.', '', $raw);
            }
        }

        return $raw;
    }
}
