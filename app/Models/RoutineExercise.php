<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineExercise extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'routine_id',
        'exercise_id',
        'order',
        'target_sets',
        'target_reps',
        'target_rest_seconds',
    ];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
