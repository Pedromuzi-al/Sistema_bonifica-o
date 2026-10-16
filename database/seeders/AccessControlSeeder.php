<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['create_ideas', 'vote_ideas', 'evaluate_ideas', 'manage_admin', 'view_author_identity', 'view_audit_logs'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        foreach (['employee' => ['create_ideas', 'vote_ideas'], 'evaluator' => ['create_ideas', 'vote_ideas', 'evaluate_ideas'], 'admin' => ['create_ideas', 'vote_ideas', 'evaluate_ideas', 'manage_admin', 'view_author_identity', 'view_audit_logs']] as $name => $permissions) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->givePermissionTo($permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
