<?php

namespace App\Notifications;

use App\Contracts\HasDatabasePayload;
use App\Models\Product;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockAlert extends Notification implements HasDatabasePayload
{
    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(public Collection $products) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Stok menipis',
            'message' => $this->products->count().' produk perlu restock: '.$this->products
                ->take(3)
                ->map(fn ($product) => $product->name)
                ->implode(', ')
                .($this->products->count() > 3 ? '…' : ''),
            'action_url' => route('master.products.index'),
            'products' => $this->products
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                ])
                ->all(),
        ];
    }
}
