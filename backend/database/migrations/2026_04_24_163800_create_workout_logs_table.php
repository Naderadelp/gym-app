<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('set_number');
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->decimal('distance_km', 6, 3)->nullable();
            $table->unsignedTinyInteger('rpe')->nullable();
            $table->string('set_type')->default('normal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
    }
};
