<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('registers a new member and returns a token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'mobile'                => '01012345678',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'age'                   => 25,
        'height'                => 175.5,
        'weight'                => 80.0,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token']]);

    expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
});

it('assigns member role on registration', function () {
    $this->postJson('/api/auth/register', [
        'name'                  => 'Jane Doe',
        'email'                 => 'jane@example.com',
        'mobile'                => '01087654321',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $user = User::where('email', 'jane@example.com')->first();
    expect($user->hasRole('member'))->toBeTrue();
});

it('fails with duplicate email', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $this->postJson('/api/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'mobile'                => '01099999999',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('fails with duplicate mobile', function () {
    User::factory()->create(['mobile' => '01012345678']);

    $this->postJson('/api/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'new@example.com',
        'mobile'                => '01012345678',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertStatus(422)->assertJsonValidationErrors('mobile');
});

it('fails with missing required fields', function () {
    $this->postJson('/api/auth/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'mobile', 'password']);
});
