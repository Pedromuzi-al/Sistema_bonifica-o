<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Notification;
use App\Models\Sector;
use App\Models\User;
use App\Models\VotingCycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SimpleIdeaExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function proposal(): array
    {
        return ['title' => 'Organizar pedidos', 'problem_description' => 'Pedidos são esquecidos.', 'solution_description' => 'Uma fila compartilhada.', 'origin_sector_id' => Sector::first()->id, 'beneficiary_sectors' => Sector::pluck('id')->all(), 'expected_results' => 'Menos esquecimentos.', 'resources_needed' => 'Ainda não sei.', 'additional_information' => 'Exemplo de uma situação.'];
    }

    private function voting(): array
    {
        $author = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $voter = User::factory()->create();
        $voter->assignRole('employee');
        $idea = Idea::create(['author_id' => $author->id, 'origin_sector_id' => Sector::first()->id, 'title' => 'Título interno', 'problem_description' => 'Texto privado que identifica a equipe', 'solution_description' => 'Solução privada', 'expected_results' => 'Estimativas privadas', 'resources_needed' => 'Recursos', 'status' => 'voting', 'is_public' => true, 'published_at' => now(), 'publication' => ['title' => 'Publicação atual diferente']]);
        $cycle = VotingCycle::create(['name' => 'Votação da equipe', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'status' => 'open']);
        $criterion = $cycle->criteria()->create(['name' => 'Impacto operacional', 'description' => 'Quanto melhorou o trabalho?', 'weight' => 100]);
        $cycle->ideas()->attach($idea->id, ['snapshot' => json_encode(['publication' => ['title' => 'Publicação revisada do ciclo', 'problem' => 'Problema revisado', 'solution' => 'Solução revisada', 'results' => 'Resultado comprovado', 'sectors' => ['Operações']]])]);
        $cycle->update(['frozen_configuration' => ['criteria' => [$criterion->toArray()], 'allow_abstention' => false]]);

        return [$author, $voter, $idea, $cycle, $criterion];
    }

    public function test_review_does_not_create_idea_and_confirmation_uses_authenticated_author(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $before = Idea::count();
        $data = [...$this->proposal(), 'author_id' => 999, 'is_public' => true];
        $this->actingAs($user)->post(route('ideas.review'), $data)->assertOk()->assertSeeText('Está tudo certo com sua ideia?')->assertSeeText('Organizar pedidos');
        $this->assertSame($before, Idea::count());
        $this->post(route('ideas.store'), [...$data, '_action' => 'send'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('ideas', ['title' => 'Organizar pedidos', 'author_id' => $user->id, 'is_public' => false]);
    }

    public function test_editing_review_preserves_all_fields_without_creating_idea(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $before = Idea::count();
        $data = $this->proposal();
        $this->actingAs($user)->post(route('ideas.store'), [...$data, '_action' => 'edit'])->assertRedirect(route('ideas.create'))->assertSessionHas('_old_input', $data);
        $this->get(route('ideas.create'))->assertOk()->assertSee('value="Organizar pedidos"', false)->assertSeeText('Todos os setores');
        $this->assertSame($before, Idea::count());
    }

    public function test_review_validates_required_fields_and_permissions(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->actingAs($user)->post(route('ideas.review'), [])->assertSessionHasErrors(['title', 'problem_description', 'beneficiary_sectors']);
        $unassigned = User::factory()->create();
        $this->actingAs($unassigned)->postJson(route('ideas.review'), $this->proposal())->assertForbidden();
    }

    public function test_review_escapes_user_text(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->actingAs($user)->post(route('ideas.review'), [...$this->proposal(), 'title' => '<script>alert(1)</script>'])->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_voting_page_displays_only_frozen_publication_and_explained_scores(): void
    {
        [$author, $voter, $idea, $cycle] = $this->voting();
        $this->actingAs($voter)->get(route('ideas.vote-form', [$idea, $cycle]))->assertOk()->assertSeeText('Publicação revisada do ciclo')->assertSeeText('Muito baixo')->assertSeeText('Muito alto')->assertDontSeeText('Texto privado que identifica a equipe')->assertDontSeeText('Publicação atual diferente')->assertDontSeeText($author->name)->assertSeeText('Confira suas respostas');
    }

    #[TestWith(['registered', 'Seu voto já foi registrado'])]
    #[TestWith(['own', 'Esta ideia é sua'])]
    #[TestWith(['scheduled', 'Você poderá votar a partir'])]
    #[TestWith(['closed', 'Esta votação foi encerrada'])]
    #[TestWith(['conflict', 'Você declarou um conflito'])]
    #[TestWith(['permission', 'Seu acesso ainda não permite votar'])]
    public function test_unavailable_voting_explains_the_specific_reason(string $state, string $message): void
    {
        [$author, $voter, $idea, $cycle] = $this->voting();
        if ($state === 'registered') {
            $idea->votes()->create(['user_id' => $voter->id, 'voting_cycle_id' => $cycle->id, 'status' => 'cast']);
        } elseif ($state === 'own') {
            $voter = $author;
        } elseif ($state === 'scheduled') {
            $this->travel(-2)->hours();
        } elseif ($state === 'closed') {
            $this->travel(2)->hours();
        } elseif ($state === 'conflict') {
            DB::table('idea_conflicts')->insert(['idea_id' => $idea->id, 'user_id' => $voter->id, 'reason' => 'Impedimento']);
        } elseif ($state === 'permission') {
            $voter->removeRole('employee');
            $voter->assignRole('evaluator');
            $voter->getRoleNames();
            $role = Role::findByName('evaluator');
            $role->revokePermissionTo('vote_ideas');
            $voter->unsetRelation('roles');
        }
        $this->actingAs($voter)->get(route('ideas.vote-form', [$idea, $cycle]))->assertOk()->assertSeeText($message)->assertDontSee('name="scores[', false);
    }

    public function test_vote_confirmation_redirects_to_receipt_and_cannot_be_repeated(): void
    {
        [$author, $voter, $idea, $cycle, $criterion] = $this->voting();
        $data = ['voting_cycle_id' => $cycle->id, 'scores' => [$criterion->id => 4]];
        $this->actingAs($voter)->post(route('ideas.vote', $idea), $data)->assertSessionHasNoErrors()->assertRedirect(route('ideas.vote-form', [$idea, $cycle]));
        $this->get(route('ideas.vote-form', [$idea, $cycle]))->assertOk()->assertSeeText('Seu voto já foi registrado');
        $this->post(route('ideas.vote', $idea), $data)->assertSessionHasErrors('vote');
        $this->assertSame(1, $idea->votes()->count());
    }

    public function test_voting_page_rejects_private_ideas_and_wrong_cycles(): void
    {
        [$author, $voter, $idea, $cycle] = $this->voting();
        $cycle->ideas()->detach($idea->id);
        $this->actingAs($voter)->get(route('ideas.vote-form', [$idea, $cycle]))->assertNotFound();
        $idea->update(['is_public' => false]);
        $this->getJson(route('ideas.vote-form', [$idea, $cycle]))->assertForbidden();
    }

    public function test_notifications_are_private_paginated_and_marking_all_affects_only_owner(): void
    {
        $owner = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $other = User::factory()->create();
        for ($index = 1; $index <= 21; $index++) {
            Notification::create(['user_id' => $owner->id, 'title' => 'Aviso '.$index, 'message' => 'Novidade da minha proposta']);
        }
        $private = Notification::create(['user_id' => $other->id, 'title' => 'Segredo de outra conta', 'message' => 'Mensagem privada de outro funcionário']);
        $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSeeText('21 avisos não lidos')->assertDontSeeText('Segredo de outra conta')->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 20);
        $this->post(route('notifications.read-all'))->assertSessionHasNoErrors();
        $this->assertSame(0, $owner->notifications()->whereNull('read_at')->count());
        $this->assertNull($private->fresh()->read_at);
        $this->get(route('notifications.index', ['unread' => 1]))->assertOk()->assertSeeText('Você está em dia!');
    }

    public function test_invalid_confirmation_returns_to_editable_form_with_input(): void
    {
        $user = User::where('email', 'funcionario@corp.local')->firstOrFail();
        $this->actingAs($user)->from(route('ideas.review'))->post(route('ideas.store'), [...$this->proposal(), 'title' => ''])->assertRedirect(route('ideas.create'))->assertSessionHasErrors('title');
        $this->get(route('ideas.create'))->assertOk()->assertSeeText('Pedidos são esquecidos.');
    }
}
