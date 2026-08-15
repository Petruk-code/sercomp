<?php

namespace App\Support;

use App\Models\Product;

final class StockManager
{
    /**
     * Increase a product's stock and record the change in the audit log.
     */
    public static function increment(Product $product, int $quantity, string $reason): void
    {
        $before = (int) $product->stock;

        // The audit entry is recorded here with the reason; suppress the
        // model events so ProductObserver does not log the same change twice.
        Product::withoutEvents(fn () => $product->increment('stock', $quantity));
        $product->refresh();

        self::logChange($product, $before, $reason);
    }

    /**
     * Decrease a product's stock and record the change in the audit log.
     */
    public static function decrement(Product $product, int $quantity, string $reason): void
    {
        $before = (int) $product->stock;

        Product::withoutEvents(fn () => $product->decrement('stock', $quantity));
        $product->refresh();

        self::logChange($product, $before, $reason);
    }

    private static function logChange(Product $product, int $before, string $reason): void
    {
        AuditLogger::record(
            'product.stock_changed',
            __('Stok :name: :before → :after (:reason).', [
                'name' => $product->name,
                'before' => $before,
                'after' => $product->stock,
                'reason' => $reason,
            ]),
            $product,
            ['stock' => $before],
            ['stock' => $product->stock],
        );
    }
}
