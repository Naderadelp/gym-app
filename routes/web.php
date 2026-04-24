<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\AnalyticsController;
use App\Http\Controllers\Web\BodyMetricController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExerciseController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\RoutineController;
use App\Http\Controllers\Web\WorkoutLogController;
use App\Http\Controllers\Web\WorkoutSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::post('/body-metrics', [BodyMetricController::class, 'store'])->name('body-metrics.store');

    Route::get('/exercises', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::post('/exercises', [ExerciseController::class, 'store'])->name('exercises.store');

    Route::post('/routines/generate', [RoutineController::class, 'generate'])->name('routines.generate');
    Route::resource('routines', RoutineController::class)->names([
        'index'   => 'routines.index',
        'create'  => 'routines.create',
        'store'   => 'routines.store',
        'show'    => 'routines.show',
        'edit'    => 'routines.edit',
        'update'  => 'routines.update',
        'destroy' => 'routines.destroy',
    ]);

    Route::post('/workouts/start', [WorkoutSessionController::class, 'create'])->name('workouts.start');
    Route::get('/workouts/{session}', [WorkoutSessionController::class, 'show'])->name('workouts.show');
    Route::post('/workouts/{session}/finish', [WorkoutSessionController::class, 'finish'])->name('workouts.finish');
    Route::post('/workouts/{session}/logs', [WorkoutLogController::class, 'store'])->name('workout-logs.store.web');
    Route::delete('/workout-logs/{log}', [WorkoutLogController::class, 'destroy'])->name('workout-logs.destroy.web');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

Route::redirect('/', '/dashboard');
