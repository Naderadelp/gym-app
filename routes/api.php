<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\WorkoutLogController;
use App\Http\Controllers\WorkoutPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('exercises', ExerciseController::class);

    Route::apiResource('workout-plans', WorkoutPlanController::class);
    Route::post('workout-plans/{workoutPlan}/exercises', [WorkoutPlanController::class, 'attachExercise']);
    Route::delete('workout-plans/{workoutPlan}/exercises/{exercise}', [WorkoutPlanController::class, 'detachExercise']);

    Route::get('logs', [WorkoutLogController::class, 'myLogs']);
    Route::post('logs', [WorkoutLogController::class, 'store']);
    Route::get('members/{member}/logs', [WorkoutLogController::class, 'memberLogs']);
    Route::get('members/{member}/progress', [WorkoutLogController::class, 'memberProgress']);

    Route::get('admin/stats', [AdminController::class, 'stats']);
    Route::put('admin/users/{user}/role', [AdminController::class, 'assignRole']);
});
