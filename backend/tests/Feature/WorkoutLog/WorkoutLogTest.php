<?php

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutLog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── MY LOGS ────────────────────────────────────────────────────────────────

it('member can list their own logs', function () {
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    WorkoutLog::factory(3)->create(['member_id' => $member->id, 'exercise_id' => $exercise->id]);

    $this->actingAs($member)
        ->getJson('/api/logs')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

it('member only sees their own logs not others', function () {
    $member1  = User::factory()->create()->assignRole('member');
    $member2  = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    WorkoutLog::factory(2)->create(['member_id' => $member1->id, 'exercise_id' => $exercise->id]);
    WorkoutLog::factory(3)->create(['member_id' => $member2->id, 'exercise_id' => $exercise->id]);

    $this->actingAs($member1)
        ->getJson('/api/logs')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('can filter logs by exercise_id', function () {
    $member     = User::factory()->create()->assignRole('member');
    $exercise1  = Exercise::factory()->create();
    $exercise2  = Exercise::factory()->create();

    WorkoutLog::factory(2)->create(['member_id' => $member->id, 'exercise_id' => $exercise1->id]);
    WorkoutLog::factory(1)->create(['member_id' => $member->id, 'exercise_id' => $exercise2->id]);

    $this->actingAs($member)
        ->getJson("/api/logs?filter[exercise_id]={$exercise1->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('unauthenticated user cannot list logs', function () {
    $this->getJson('/api/logs')->assertUnauthorized();
});

// ── STORE ──────────────────────────────────────────────────────────────────

it('member can log a workout', function () {
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)
        ->postJson('/api/logs', [
            'exercise_id' => $exercise->id,
            'sets_done'   => 4,
            'reps_done'   => 12,
            'weight'      => 80.0,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.sets_done', 4);

    expect(WorkoutLog::where('member_id', $member->id)->count())->toBe(1);
});

it('auto-sets member_id to the authenticated user', function () {
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)
        ->postJson('/api/logs', [
            'exercise_id' => $exercise->id,
            'sets_done'   => 3,
        ]);

    expect(WorkoutLog::first()->member_id)->toBe($member->id);
});

it('auto-sets logged_at when not provided', function () {
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)
        ->postJson('/api/logs', [
            'exercise_id' => $exercise->id,
            'sets_done'   => 3,
        ]);

    expect(WorkoutLog::first()->logged_at)->not->toBeNull();
});

it('trainer cannot log a workout', function () {
    $trainer  = User::factory()->create()->assignRole('trainer');
    $exercise = Exercise::factory()->create();

    $this->actingAs($trainer)
        ->postJson('/api/logs', [
            'exercise_id' => $exercise->id,
            'sets_done'   => 3,
        ])
        ->assertForbidden();
});

it('fails store validation with missing sets_done', function () {
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)
        ->postJson('/api/logs', ['exercise_id' => $exercise->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('sets_done');
});

// ── MEMBER LOGS (trainer/admin view) ──────────────────────────────────────

it('trainer can view a specific member logs', function () {
    $trainer  = User::factory()->create()->assignRole('trainer');
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    WorkoutLog::factory(4)->create(['member_id' => $member->id, 'exercise_id' => $exercise->id]);

    $this->actingAs($trainer)
        ->getJson("/api/members/{$member->id}/logs")
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('admin can view a specific member logs', function () {
    $admin    = User::factory()->create()->assignRole('admin');
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    WorkoutLog::factory(2)->create(['member_id' => $member->id, 'exercise_id' => $exercise->id]);

    $this->actingAs($admin)
        ->getJson("/api/members/{$member->id}/logs")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('member cannot view another members logs', function () {
    $member1  = User::factory()->create()->assignRole('member');
    $member2  = User::factory()->create()->assignRole('member');

    $this->actingAs($member1)
        ->getJson("/api/members/{$member2->id}/logs")
        ->assertForbidden();
});

// ── MEMBER PROGRESS ────────────────────────────────────────────────────────

it('trainer can view member progress summary', function () {
    $trainer  = User::factory()->create()->assignRole('trainer');
    $member   = User::factory()->create()->assignRole('member');
    $exercise = Exercise::factory()->create();

    WorkoutLog::factory(5)->create([
        'member_id'   => $member->id,
        'exercise_id' => $exercise->id,
        'sets_done'   => 3,
        'reps_done'   => 10,
    ]);

    $response = $this->actingAs($trainer)
        ->getJson("/api/members/{$member->id}/progress")
        ->assertOk();

    expect($response->json('data.summary.total_sessions'))->toBe(5)
        ->and($response->json('data.summary.total_sets'))->toBe(15)
        ->and($response->json('data.summary.exercises_logged'))->toBe(1);
});

it('member cannot view another members progress', function () {
    $member1 = User::factory()->create()->assignRole('member');
    $member2 = User::factory()->create()->assignRole('member');

    $this->actingAs($member1)
        ->getJson("/api/members/{$member2->id}/progress")
        ->assertForbidden();
});

// ── ADMIN STATS ────────────────────────────────────────────────────────────

it('admin can view dashboard stats', function () {
    $admin   = User::factory()->create()->assignRole('admin');
    $trainer = User::factory()->create()->assignRole('trainer');
    User::factory(3)->create()->each->assignRole('member');

    $this->actingAs($admin)
        ->getJson('/api/admin/stats')
        ->assertOk()
        ->assertJsonPath('data.total_members', 3)
        ->assertJsonPath('data.total_trainers', 1)
        ->assertJsonStructure(['data' => [
            'total_members', 'total_trainers', 'total_exercises',
            'active_plans', 'recent_logs',
        ]]);
});

it('trainer cannot view admin stats', function () {
    $trainer = User::factory()->create()->assignRole('trainer');

    $this->actingAs($trainer)
        ->getJson('/api/admin/stats')
        ->assertForbidden();
});

it('member cannot view admin stats', function () {
    $member = User::factory()->create()->assignRole('member');

    $this->actingAs($member)
        ->getJson('/api/admin/stats')
        ->assertForbidden();
});
