<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    protected $fillable = [
        'name',
        'display_name',
        'unit_preference',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function bodyMetrics(): HasMany
    {
        return $this->hasMany(BodyMetric::class);
    }

    public function routines(): HasMany
    {
        return $this->hasMany(Routine::class);
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function generateTokenString(): string
    {
        return sprintf(
            '%s%s%s',
            config('sanctum.token_prefix', ''),
            $tokenEntropy = Str::random(128),
            hash('crc32b', $tokenEntropy)
        );
    }
}
