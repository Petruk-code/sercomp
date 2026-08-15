<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => fake()->unique()->numerify('INV-########'),
            'type' => 'retail',
            'service_order_id' => null,
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'payment_method' => fake()->randomElement(Sale::PAYMENT_METHODS),
            'payment_status' => 'paid',
            'paid_amount' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
