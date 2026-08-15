<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrder>
 */
class ServiceOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => fake()->unique()->numerify('SRV-########'),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'technician_id' => null,
            'item_name' => fake()->randomElement(['Laptop', 'PC Desktop', 'Smartphone', 'Monitor', 'Printer']).' '.fake()->randomElement(['ASUS', 'Lenovo', 'HP', 'Dell', 'Xiaomi']),
            'serial_number' => fake()->optional()->bothify('SN-????-####'),
            'complaint' => fake()->sentence(),
            'physical_condition' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(ServiceOrder::STATUSES),
            'estimated_cost' => fake()->numberBetween(50_000, 500_000),
            'labor_cost' => 0,
            'total_cost' => 0,
            'notes' => fake()->optional()->sentence(),
            'estimated_completion_at' => fake()->optional()->dateTimeBetween('now', '+1 week'),
        ];
    }
}
