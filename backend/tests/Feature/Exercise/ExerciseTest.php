<?php

use App\Models\Exercise;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── INDEX ──────────────────────────────────────────────────────────────────

it('returns paginated exercises for any authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    Exercise::factory(5)->create();

    $this->actingAs($user)
        ->getJson('/api/exercises')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
});

it('requires authentication to list exercises', function () {
    $this->getJson('/api/exercises')->assertUnauthorized();
});

it('can filter exercises by category', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    Exercise::factory()->create(['category' => 'Strength']);
    Exercise::factory()->create(['category' => 'Cardio']);

    $this->actingAs($user)
        ->getJson('/api/exercises?filter[category]=Strength')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('can sort exercises by name ascending', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    Exercise::factory()->create(['name' => 'Zzz Exercise']);
    Exercise::factory()->create(['name' => 'Aaa Exercise']);

    $response = $this->actingAs($user)
        ->getJson('/api/exercises?sort=name')
        ->assertOk();

    expect($response->json('data.0.name'))->toBe('Aaa Exercise');
});

// ── STORE ──────────────────────────────────────────────────────────────────

it('allows admin to create an exercise', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/exercises', [
            'name'         => 'Bench Press',
            'category'     => 'Strength',
            'muscle_group' => 'Chest',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Bench Press');

    expect(Exercise::where('name', 'Bench Press')->exists())->toBeTrue();
});

it('allows trainer to create an exercise', function () {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $this->actingAs($trainer)
        ->postJson('/api/exercises', [
            'name'         => 'Pull Up',
            'category'     => 'Strength',
            'muscle_group' => 'Back',
        ])
        ->assertStatus(201);
});

it('forbids member from creating an exercise', function () {
    $member = User::factory()->create();
    $member->assignRole('member');

    $this->actingAs($member)
        ->postJson('/api/exercises', [
            'name'         => 'Push Up',
            'category'     => 'Strength',
            'muscle_group' => 'Chest',
        ])
        ->assertForbidden();
});

it('fails store validation with missing required fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/exercises', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'category', 'muscle_group']);
});

// ── SHOW ───────────────────────────────────────────────────────────────────

it('allows any authenticated user to view a single exercise', function () {
    $member   = User::factory()->create();
    $member->assignRole('member');
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)
        ->getJson("/api/exercises/{$exercise->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $exercise->id);
});

it('returns 404 for a non-existent exercise', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/api/exercises/9999')
        ->assertNotFound();
});

// ── UPDATE ─────────────────────────────────────────────────────────────────

it('allows trainer to update an exercise', function () {
    $trainer  = User::factory()->create();
    $trainer->assignRole('trainer');
    $exercise = Exercise::factory()->create();

    $this->actingAs($trainer)
        ->putJson("/api/exercises/{$exercise->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

it('forbids member from updating an exercise', function () {
    $member   = User::factory()->create();
    $member->assignRole('member');
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)
        ->putJson("/api/exercises/{$exercise->id}", ['name' => 'Hacked'])
        ->assertForbidden();
});

// ── DELETE ─────────────────────────────────────────────────────────────────

it('allows admin to delete an exercise', function () {
    $admin    = User::factory()->create();
    $admin->assignRole('admin');
    $exercise = Exercise::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/exercises/{$exercise->id}")
        ->assertOk();

    expect(Exercise::find($exercise->id))->toBeNull();
});

it('forbids trainer from deleting an exercise', function () {
    $trainer  = User::factory()->create();
    $trainer->assignRole('trainer');
    $exercise = Exercise::factory()->create();

    $this->actingAs($trainer)
        ->deleteJson("/api/exercises/{$exercise->id}")
        ->assertForbidden();
});

it('forbids member from deleting an exercise', function () {
    $member   = User::factory()->create();
    $member->assignRole('member');
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)
        ->deleteJson("/api/exercises/{$exercise->id}")
        ->assertForbidden();
});
