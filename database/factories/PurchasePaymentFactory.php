<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePayment>
 */
class PurchasePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'amount' => fake()->numberBetween(1_000, 500_000),
            'payment_method' => fake()->randomElement(Purchase::PAYMENT_METHODS),
            'paid_at' => fake()->dateTime(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
