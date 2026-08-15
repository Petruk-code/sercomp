<?php

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderStatusHistory>
 */
class ServiceOrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'status' => fake()->randomElement(ServiceOrder::STATUSES),
            'user_id' => User::factory(),
        ];
    }
}
