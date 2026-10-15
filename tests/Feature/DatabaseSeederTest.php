<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_preserves_records_passwords_and_workflow(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $admin->update(['name' => 'Nome personalizado', 'password' => 'senha-personalizada']);
        $idea = Idea::firstOrFail();
        $idea->update(['status' => 'triage']);
        $tables = ['users', 'sectors', 'ideas', 'idea_beneficiary_sectors', 'idea_status_histories', 'voting_cycles', 'voting_criteria', 'roles', 'permissions'];
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        $this->seed(DatabaseSeeder::class);

        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame('Nome personalizado', $admin->fresh()->name);
        $this->assertTrue(Hash::check('senha-personalizada', $admin->fresh()->password));
        $this->assertSame('triage', $idea->fresh()->status);
    }

    public function test_reseeding_does_not_restore_deactivated_demo_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $user->delete();

        $this->seed(DatabaseSeeder::class);

        $this->assertSoftDeleted($user);
        $this->assertDatabaseCount('users', 3);
    }
}
