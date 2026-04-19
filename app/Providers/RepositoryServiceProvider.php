<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\ExerciseRepositoryInterface::class,
            \App\Repositories\Eloquent\ExerciseRepository::class,
        );

        $this->app->bind(
            \App\Repositories\Contracts\WorkoutPlanRepositoryInterface::class,
            \App\Repositories\Eloquent\WorkoutPlanRepository::class,
        );
    }
}
