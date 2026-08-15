<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class InvoiceNumber
{
    /**
     * Generate the next invoice number for the given prefix (e.g. 'INV', 'SRV').
     *
     * Numbers are allocated atomically per prefix + day using a dedicated
     * sequence table, so concurrent requests never produce duplicates. A gap is
     * left whenever a sale is later rolled back; that is intentional.
     */
    public static function next(string $prefix): string
    {
        $date = now()->toDateString();
        $datePart = str_replace('-', '', $date);

        return DB::transaction(function () use ($prefix, $date, $datePart): string {
            if (! DB::table('invoice_sequences')->where('prefix', $prefix)->where('date', $date)->exists()) {
                DB::table('invoice_sequences')->insertOrIgnore([
                    'prefix' => $prefix,
                    'date' => $date,
                    'last_number' => self::currentMax($prefix, $datePart),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $sequence = DB::table('invoice_sequences')
                ->where('prefix', $prefix)
                ->where('date', $date)
                ->lockForUpdate()
                ->first();

            $next = $sequence->last_number + 1;

            DB::table('invoice_sequences')
                ->where('prefix', $prefix)
                ->where('date', $date)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return $prefix.'-'.$datePart.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Highest trailing sequence currently stored for the prefix + day, so the
     * counter can pick up where pre-existing rows left off.
     */
    private static function currentMax(string $prefix, string $datePart): int
    {
        [$table, $column] = match ($prefix) {
            'SRV' => ['service_orders', 'invoice_number'],
            'PO' => ['purchases', 'purchase_number'],
            'RTN' => ['purchase_returns', 'return_number'],
            default => ['sales', 'invoice_number'],
        };
        $like = $prefix.'-'.$datePart.'-%';

        return (int) (DB::table($table)
            ->where($column, 'like', $like)
            ->pluck($column)
            ->map(fn (string $number): int => (int) substr($number, strrpos($number, '-') + 1))
            ->max() ?? 0);
    }
}
