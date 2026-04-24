<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'mobile'            => fake()->unique()->numerify('010########'),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'age'               => fake()->numberBetween(18, 60),
            'height'            => fake()->randomFloat(2, 155, 200),
            'weight'            => fake()->randomFloat(2, 50, 120),
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
