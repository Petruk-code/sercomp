<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
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
            'purchase_id' => Purchase::factory(),
            'product_id' => null,
            'name' => fake()->words(2, true),
            'quantity' => $quantity,
            'purchase_price' => $price,
            'subtotal' => $price * $quantity,
        ];
    }
}
