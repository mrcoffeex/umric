<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $categories = ['Computer Science', 'Engineering', 'Business', 'Science', 'Humanities', 'Medicine', 'Law', 'Education'];
        $category = $this->faker->randomElement($categories);

        return [
            'name' => $category,
            'slug' => str()->slug($category),
            'description' => $this->faker->sentence(),
        ];
    }
}
