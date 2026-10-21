<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Equipes</h2><p class="mt-2 text-slate-600">Organize a empresa. Desativar um cadastro preserva seu histórico.</p></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
        <div class="flex flex-wrap gap-3"><a class="apple-button" href="{{ route('admin.sectors.index') }}">Ver setores</a><a class="apple-button" href="{{ route('admin.teams.create') }}">Nova equipe</a><a class="apple-button--light" href="{{ route('admin.users.index') }}">Pessoas e acessos</a><a class="apple-button--light" href="{{ route('admin.index') }}">Administração</a></div>
        <form method="GET" class="apple-card flex flex-wrap items-end gap-3 p-5"><x-workflow-field name="search" label="Buscar equipe" :value="request('search')" :required="false" /><button class="apple-button">Pesquisar</button></form>

        <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">Equipes</h3><p class="text-sm">Uma equipe organiza pessoas. Participar dela não concede permissões administrativas.</p>@forelse ($teams as $team)<div class="flex flex-wrap items-center justify-between gap-3 border-t py-4"><div><h4 class="font-semibold">{{ $team->name }} · {{ $team->is_active ? 'Ativa' : 'Inativa' }}</h4><p class="text-sm">{{ $team->sector?->name ?? 'Equipe entre setores' }} · {{ $team->users_count }} participantes</p></div><a class="apple-button--light" href="{{ route('admin.teams.edit', $team) }}">Editar equipe</a></div>@empty<p>Nenhuma equipe encontrada.</p>@endforelse{{ $teams->links() }}</section>
    </div>
</x-app-layout>
