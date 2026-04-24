<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'set_number',
        'weight',
        'reps',
        'duration_seconds',
        'distance_km',
        'rpe',
        'set_type',
    ];

    protected function casts(): array
    {
        return [
            'weight'      => 'decimal:2',
            'distance_km' => 'decimal:3',
        ];
    }

    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
