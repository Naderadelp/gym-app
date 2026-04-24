<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'weight',
        'height',
        'body_fat_percentage',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'weight'              => 'decimal:2',
            'height'              => 'decimal:2',
            'body_fat_percentage' => 'decimal:2',
            'logged_at'           => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
