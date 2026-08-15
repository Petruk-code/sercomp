<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory()->product(),
            'name' => fake()->unique()->words(3, true),
            'sku' => fake()->unique()->numerify('SKU-#####'),
            'barcode' => fake()->unique()->numerify('899###########'),
            'unit' => 'pcs',
            'purchase_price' => fake()->numberBetween(10_000, 100_000),
            'selling_price' => fake()->numberBetween(15_000, 150_000),
            'stock' => fake()->numberBetween(0, 50),
            'min_stock' => 5,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
