<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ServiceCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCatalog>
 */
class ServiceCatalogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory()->service(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->numberBetween(25_000, 250_000),
            'estimated_minutes' => fake()->numberBetween(30, 480),
        ];
    }
}
