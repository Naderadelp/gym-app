<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutPlan>
 */
class WorkoutPlanFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $end   = fake()->dateTimeBetween($start, '+3 months');

        return [
            'trainer_id'  => User::factory(),
            'member_id'   => User::factory(),
            'name'        => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'start_date'  => $start,
            'end_date'    => $end,
            'status'      => fake()->randomElement(['active', 'completed', 'paused']),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }
}
