<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'return_number' => fake()->unique()->numerify('RTN-########'),
            'purchase_id' => Purchase::factory(),
            'user_id' => User::factory(),
            'return_date' => fake()->date(),
            'result_type' => fake()->randomElement(PurchaseReturn::RESULT_TYPES),
            'reason' => fake()->optional()->sentence(),
            'total' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
