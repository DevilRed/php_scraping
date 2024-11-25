<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->title,
            'price' => $this->faker->randomNumber(2),
            'rating' => $this->faker->randomNumber(1),
            'in_stock' => $this->faker->randomElement(['In stock', 'Out stock']),
            'details_url' => $this->faker->url(),
            'image_url' => $this->faker->url(),
        ];
    }
}
