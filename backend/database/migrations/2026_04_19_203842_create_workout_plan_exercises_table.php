<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedSmallInteger('target_sets');
            $table->unsignedSmallInteger('target_reps')->nullable();
            $table->unsignedSmallInteger('target_rest_seconds')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_exercises');
    }
};
