<?php

namespace Database\Factories;

use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'         => fake()->unique()->words(3, true),
            'description'  => fake()->optional()->sentence(),
            'category'     => fake()->randomElement(['Strength', 'Cardio', 'Flexibility']),
            'muscle_group' => fake()->randomElement(['Chest', 'Back', 'Legs', 'Shoulders', 'Arms', 'Core']),
        ];
    }
}
