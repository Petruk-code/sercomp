<?php

namespace App\Observers;

use App\Models\Product;
use App\Support\AuditLogger;

class ProductObserver
{
    /**
     * Log stock changes made through a regular model save (e.g. the product
     * edit form). Increment/decrement flows bypass model events and are
     * handled by StockManager instead.
     */
    public function updated(Product $product): void
    {
        if (! $product->isDirty('stock')) {
            return;
        }

        $before = (int) $product->getOriginal('stock');

        AuditLogger::record(
            'product.stock_changed',
            __('Stok :name: :before → :after (diubah manual).', [
                'name' => $product->name,
                'before' => $before,
                'after' => $product->stock,
            ]),
            $product,
            ['stock' => $before],
            ['stock' => $product->stock],
        );
    }
}
