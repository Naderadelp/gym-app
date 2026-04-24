<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BodyMetricController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\RoutineGeneratorController;
use App\Http\Controllers\WorkoutLogController;
use App\Http\Controllers\WorkoutSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
});

// V1 API — Personal Workout Tracker
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // US1 — Profile & Body Metrics
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::apiResource('body-metrics', BodyMetricController::class)->only(['index', 'store', 'destroy']);

    // US2 — Exercise Library
    Route::apiResource('exercises', ExerciseController::class);

    // US3 — Routines & Smart Generator
    Route::post('routines/generate', [RoutineGeneratorController::class, 'generate']);
    Route::apiResource('routines', RoutineController::class);

    // US4 — Workout Sessions & Logging
    Route::apiResource('workout-sessions', WorkoutSessionController::class);
    Route::post('workout-sessions/{workoutSession}/finish', [WorkoutSessionController::class, 'finish']);
    Route::post('workout-sessions/{workoutSession}/logs', [WorkoutLogController::class, 'store']);
    Route::delete('workout-logs/{workoutLog}', [WorkoutLogController::class, 'destroy']);

    // US5 — Analytics
    Route::get('analytics/volume', [AnalyticsController::class, 'volume']);
    Route::get('analytics/personal-records', [AnalyticsController::class, 'personalRecords']);
});
