<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('revokes the current token on logout', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->tokens()->count())->toBe(0);
});

it('does not revoke other tokens on logout', function () {
    $user   = User::factory()->create();
    $active = $user->createToken('mobile')->plainTextToken;
    $user->createToken('other-device');

    $this->withToken($active)->postJson('/api/auth/logout')->assertOk();

    expect($user->tokens()->count())->toBe(1);
});

it('returns 401 when logging out without a token', function () {
    $this->postJson('/api/auth/logout')->assertStatus(401);
});
