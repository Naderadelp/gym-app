<?php

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutPlan;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── helpers ────────────────────────────────────────────────────────────────

function makePlan(User $trainer, User $member, array $attrs = []): WorkoutPlan
{
    return WorkoutPlan::factory()->create(array_merge([
        'trainer_id' => $trainer->id,
        'member_id'  => $member->id,
    ], $attrs));
}

// ── INDEX ──────────────────────────────────────────────────────────────────

it('admin sees all workout plans', function () {
    $admin   = User::factory()->create()->assignRole('admin');
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');

    WorkoutPlan::factory(3)->create(['trainer_id' => $trainer->id, 'member_id' => $member->id]);

    $this->actingAs($admin)
        ->getJson('/api/workout-plans')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('trainer only sees their own plans', function () {
    $trainer1 = User::factory()->create()->assignRole('trainer');
    $trainer2 = User::factory()->create()->assignRole('trainer');
    $member   = User::factory()->create()->assignRole('member');

    WorkoutPlan::factory(2)->create(['trainer_id' => $trainer1->id, 'member_id' => $member->id]);
    WorkoutPlan::factory(1)->create(['trainer_id' => $trainer2->id, 'member_id' => $member->id]);

    $this->actingAs($trainer1)
        ->getJson('/api/workout-plans')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('member only sees their own plans', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $member1 = User::factory()->create()->assignRole('member');
    $member2 = User::factory()->create()->assignRole('member');

    WorkoutPlan::factory(2)->create(['trainer_id' => $trainer->id, 'member_id' => $member1->id]);
    WorkoutPlan::factory(1)->create(['trainer_id' => $trainer->id, 'member_id' => $member2->id]);

    $this->actingAs($member1)
        ->getJson('/api/workout-plans')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('can filter plans by status', function () {
    $admin   = User::factory()->create()->assignRole('admin');
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');

    WorkoutPlan::factory(2)->create(['trainer_id' => $trainer->id, 'member_id' => $member->id, 'status' => 'active']);
    WorkoutPlan::factory(1)->create(['trainer_id' => $trainer->id, 'member_id' => $member->id, 'status' => 'completed']);

    $this->actingAs($admin)
        ->getJson('/api/workout-plans?filter[status]=active')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

// ── STORE ──────────────────────────────────────────────────────────────────

it('trainer creates a plan with their own trainer_id auto-set', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');

    $response = $this->actingAs($trainer)->postJson('/api/workout-plans', [
        'member_id'   => $member->id,
        'name'        => 'Strength Plan',
        'start_date'  => '2026-05-01',
        'end_date'    => '2026-06-01',
        'status'      => 'active',
    ]);

    $response->assertStatus(201);
    expect(WorkoutPlan::first()->trainer_id)->toBe($trainer->id);
});

it('admin creates a plan and specifies trainer_id', function () {
    $admin   = User::factory()->create()->assignRole('admin');
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');

    $this->actingAs($admin)->postJson('/api/workout-plans', [
        'trainer_id'  => $trainer->id,
        'member_id'   => $member->id,
        'name'        => 'Admin Plan',
        'start_date'  => '2026-05-01',
        'end_date'    => '2026-06-01',
        'status'      => 'active',
    ])->assertStatus(201);
});

it('member cannot create a plan', function () {
    $member = User::factory()->create()->assignRole('member');
    $other  = User::factory()->create()->assignRole('member');

    $this->actingAs($member)->postJson('/api/workout-plans', [
        'member_id'  => $other->id,
        'name'       => 'Hacked',
        'start_date' => '2026-05-01',
        'end_date'   => '2026-06-01',
        'status'     => 'active',
    ])->assertForbidden();
});

it('fails validation when end_date is before start_date', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');

    $this->actingAs($trainer)->postJson('/api/workout-plans', [
        'member_id'  => $member->id,
        'name'       => 'Bad Plan',
        'start_date' => '2026-06-01',
        'end_date'   => '2026-05-01',
        'status'     => 'active',
    ])->assertStatus(422)->assertJsonValidationErrors('end_date');
});

// ── SHOW ───────────────────────────────────────────────────────────────────

it('returns a single plan with includes', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');
    $plan    = makePlan($trainer, $member);

    $this->actingAs($trainer)
        ->getJson("/api/workout-plans/{$plan->id}?include=trainer,member")
        ->assertOk()
        ->assertJsonPath('data.trainer.id', $trainer->id)
        ->assertJsonPath('data.member.id', $member->id);
});

// ── UPDATE ─────────────────────────────────────────────────────────────────

it('trainer can update a workout plan', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');
    $plan    = makePlan($trainer, $member);

    $this->actingAs($trainer)
        ->putJson("/api/workout-plans/{$plan->id}", ['status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

it('member cannot update a workout plan', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');
    $plan    = makePlan($trainer, $member);

    $this->actingAs($member)
        ->putJson("/api/workout-plans/{$plan->id}", ['status' => 'completed'])
        ->assertForbidden();
});

// ── DELETE ─────────────────────────────────────────────────────────────────

it('admin can delete a workout plan', function () {
    $admin   = User::factory()->create()->assignRole('admin');
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');
    $plan    = makePlan($trainer, $member);

    $this->actingAs($admin)
        ->deleteJson("/api/workout-plans/{$plan->id}")
        ->assertOk();

    expect(WorkoutPlan::find($plan->id))->toBeNull();
});

it('trainer cannot delete a workout plan', function () {
    $trainer = User::factory()->create()->assignRole('trainer');
    $member  = User::factory()->create()->assignRole('member');
    $plan    = makePlan($trainer, $member);

    $this->actingAs($trainer)
        ->deleteJson("/api/workout-plans/{$plan->id}")
        ->assertForbidden();
});

// ── ATTACH / DETACH EXERCISES ──────────────────────────────────────────────

it('trainer can attach an exercise to a plan', function () {
    $trainer  = User::factory()->create()->assignRole('trainer');
    $member   = User::factory()->create()->assignRole('member');
    $plan     = makePlan($trainer, $member);
    $exercise = Exercise::factory()->create();

    $this->actingAs($trainer)->postJson("/api/workout-plans/{$plan->id}/exercises", [
        'exercise_id'  => $exercise->id,
        'sets'         => 3,
        'reps'         => 10,
        'rest_seconds' => 60,
    ])->assertOk();

    expect($plan->exercises()->count())->toBe(1);
});

it('trainer can detach an exercise from a plan', function () {
    $trainer  = User::factory()->create()->assignRole('trainer');
    $member   = User::factory()->create()->assignRole('member');
    $plan     = makePlan($trainer, $member);
    $exercise = Exercise::factory()->create();

    $plan->exercises()->attach($exercise->id, ['sets' => 3, 'rest_seconds' => 60]);

    $this->actingAs($trainer)
        ->deleteJson("/api/workout-plans/{$plan->id}/exercises/{$exercise->id}")
        ->assertOk();

    expect($plan->exercises()->count())->toBe(0);
});

it('member cannot attach an exercise', function () {
    $trainer  = User::factory()->create()->assignRole('trainer');
    $member   = User::factory()->create()->assignRole('member');
    $plan     = makePlan($trainer, $member);
    $exercise = Exercise::factory()->create();

    $this->actingAs($member)->postJson("/api/workout-plans/{$plan->id}/exercises", [
        'exercise_id'  => $exercise->id,
        'sets'         => 3,
        'rest_seconds' => 60,
    ])->assertForbidden();
});
