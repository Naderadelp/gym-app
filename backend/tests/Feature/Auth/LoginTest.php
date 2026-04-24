<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a token on valid credentials', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token']]);
});

it('returns user data without password', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    expect($response->json('data.user'))->not->toHaveKey('password');
});

it('rejects wrong password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(401);
});

it('rejects non-existent email', function () {
    $this->postJson('/api/auth/login', [
        'email'    => 'nobody@example.com',
        'password' => 'password',
    ])->assertStatus(401);
});

it('fails validation with missing fields', function () {
    $this->postJson('/api/auth/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});
