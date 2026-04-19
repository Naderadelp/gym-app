<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'index-user', 'show-user', 'create-user', 'edit-user', 'delete-user',
            'index-exercise', 'show-exercise', 'create-exercise', 'edit-exercise', 'delete-exercise',
            'index-workout-plan', 'show-workout-plan', 'create-workout-plan', 'edit-workout-plan', 'delete-workout-plan',
            'index-workout-log', 'show-workout-log', 'create-workout-log',
            'view-admin-stats',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $trainer = Role::firstOrCreate(['name' => 'trainer']);
        $trainer->syncPermissions([
            'index-exercise', 'show-exercise', 'create-exercise', 'edit-exercise',
            'index-workout-plan', 'show-workout-plan', 'create-workout-plan', 'edit-workout-plan',
            'index-workout-log', 'show-workout-log',
        ]);

        $member = Role::firstOrCreate(['name' => 'member']);
        $member->syncPermissions([
            'index-exercise', 'show-exercise',
            'show-workout-plan',
            'index-workout-log', 'show-workout-log', 'create-workout-log',
        ]);
    }
}
