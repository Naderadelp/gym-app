<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'name'   => 'Admin User',
            'email'  => 'admin@gym.com',
            'mobile' => '01000000000',
        ]);

        $admin->assignRole('admin');
    }
}
