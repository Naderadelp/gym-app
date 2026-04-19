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
            $table->foreignId('member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('workout_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('sets_done');
            $table->unsignedSmallInteger('reps_done')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
    }
};
