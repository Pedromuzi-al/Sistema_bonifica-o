<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CycleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_has_a_create_link_and_no_creation_form(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@corp.local')->firstOrFail();
        $this->actingAs($admin)->get(route('cycles.index'))->assertOk()
            ->assertSeeText('Ciclo de votação Q4')->assertSeeText('Novo ciclo')
            ->assertSee(route('cycles.create'), false)->assertDontSee('data-cycle-purpose');
        $this->get(route('cycles.create'))->assertOk()->assertSee('data-cycle-purpose="selection"', false)->assertDontSeeText('Ciclo de votação Q4');
        $this->get(route('cycles.create', ['purpose' => 'recognition']))->assertOk()->assertSee('data-cycle-purpose="recognition"', false);
    }

    public function test_empty_listing_explains_how_to_create_the_first_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);
        VotingCycle::query()->delete();
        $this->actingAs(User::where('email', 'admin@corp.local')->firstOrFail())->get(route('cycles.index'))->assertOk()
            ->assertSeeText('Nenhum ciclo cadastrado ainda.')->assertSeeText('Novo ciclo')->assertDontSee('data-cycle-purpose');
    }

    public function test_employee_has_no_create_access_and_cannot_see_drafts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'funcionario@corp.local')->firstOrFail())->get(route('cycles.index'))->assertOk()
            ->assertSeeText('Nenhum ciclo disponível no momento.')->assertDontSeeText('Novo ciclo')->assertDontSeeText('Ciclo de votação Q4');
        $this->getJson(route('cycles.create'))->assertForbidden();
        $this->getJson(route('cycles.create', ['purpose' => 'recognition']))->assertForbidden();
    }
}
