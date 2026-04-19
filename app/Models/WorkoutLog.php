<?php

namespace App\Models;

use Database\Factories\WorkoutLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLog extends Model
{
    /** @use HasFactory<WorkoutLogFactory> */
    use HasFactory;

    protected $fillable = [
        'member_id',
        'workout_plan_id',
        'exercise_id',
        'sets_done',
        'reps_done',
        'weight',
        'duration_seconds',
        'notes',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'weight'    => 'decimal:2',
            'logged_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
