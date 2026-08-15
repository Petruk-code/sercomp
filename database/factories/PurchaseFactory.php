<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_number' => fake()->unique()->numerify('PO-########'),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'purchase_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Mark the purchase as fully paid.
     */
    public function paid(): static
    {
        return $this->state(fn (): array => [
            'paid_amount' => fn (array $attributes): float => (float) $attributes['total'],
            'payment_status' => 'paid',
        ]);
    }
}
