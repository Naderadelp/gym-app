<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:permissions')]
#[Description('Sync all roles and permissions')]
class SyncPermissions extends Command
{
    public function handle(): void
    {
        $this->call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->info('Permissions synced successfully.');
    }
}
