<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutLog>
 */
class WorkoutLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id'        => User::factory(),
            'exercise_id'      => Exercise::factory(),
            'sets_done'        => fake()->numberBetween(1, 5),
            'reps_done'        => fake()->optional()->numberBetween(5, 20),
            'weight'           => fake()->optional()->randomFloat(2, 20, 150),
            'duration_seconds' => fake()->optional()->numberBetween(30, 600),
            'notes'            => fake()->optional()->sentence(),
            'logged_at'        => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
