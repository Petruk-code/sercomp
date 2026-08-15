<?php

namespace Database\Factories;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturnItem>
 */
class PurchaseReturnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $price = fake()->numberBetween(1_000, 500_000);

        return [
            'purchase_return_id' => PurchaseReturn::factory(),
            'purchase_item_id' => null,
            'product_id' => null,
            'name' => fake()->words(2, true),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $price * $quantity,
        ];
    }
}
