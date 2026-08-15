<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->sentence(2));

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(Category::TYPES),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
        ]);
    }
}
