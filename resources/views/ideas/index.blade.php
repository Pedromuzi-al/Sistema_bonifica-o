<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ideias e propostas</h2>
            @if (auth()->user()->can('create', App\Models\Idea::class))
                <a href="{{ route('ideas.create') }}" data-permission="true" data-permission-message="Você não tem permissão para criar ideias." class="rounded-full bg-[#0faddf] px-5 py-2.5 text-sm font-semibold text-slate-950 shadow-sm shadow-sky-500/20 hover:bg-[#0b96c2]">Nova ideia</a>
            @else
                <button type="button" data-permission="false" data-permission-message="Você não tem permissão para criar ideias." class="rounded-md bg-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Nova ideia</button>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" class="apple-card mb-6 flex flex-wrap items-end gap-3 p-4">
                <x-workflow-field name="search" label="Pesquisar" :required="false" :value="request('search')" />
                <label>Status<select name="status" class="block rounded-lg"><option value="">Todos</option>@foreach (\App\Models\Idea::LABELS as $status=>$label)<option value="{{ $status }}" @selected(request('status') == $status)>{{ $label }}</option>@endforeach</select></label>
                <label><input type="checkbox" name="mine" value="1" @checked(request()->boolean('mine'))> Minhas propostas</label>
                <label><input type="checkbox" name="implemented" value="1" @checked(request()->boolean('implemented'))> Ideias Implementadas</label>
                <button class="apple-button">Filtrar</button>
            </form>
            <div class="grid gap-6">
                @forelse ($ideas as $idea)
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">{{ $idea->idea_code }} · {{ (auth()->user()->can('evaluate', $idea) || $idea->isContributor(auth()->user())) ? $idea->originSector?->name : implode(', ', $idea->publication['sectors'] ?? []) }}</p>
                                <h3 class="text-xl font-bold text-slate-900">{{ (auth()->user()->can('evaluate', $idea) || $idea->isContributor(auth()->user())) ? $idea->title : ($idea->publication['title'] ?? 'Publicação em revisão') }}</h3>
                            </div>
                            <x-idea-status-badge :status="$idea->status" />
                        </div>

                        <p class="mt-4 text-gray-700">{{ Str::limit((auth()->user()->can('evaluate', $idea) || $idea->isContributor(auth()->user())) ? $idea->solution_description : ($idea->publication['solution'] ?? ''), 240) }}</p>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Autor: {{ $idea->visibleAuthorName(auth()->user()) }}</div>
                            <div>{{ $idea->created_at->format('d/m/Y') }}</div>
                        </div>

                        <div class="mt-5">
                            <a href="{{ route('ideas.show', $idea) }}" class="font-medium text-sky-600 hover:text-sky-800">Abrir detalhe</a>
                        </div>
                    </div>
                @empty
                    <section class="apple-card space-y-3 p-6"><h3 class="font-bold">Nenhuma ideia por aqui ainda</h3><p>Se você usou filtros, tente mudar a busca. Para enviar uma sugestão, clique em “Nova ideia”.</p></section>
                @endforelse
            </div>
            <div class="mt-6">{{ $ideas->links() }}</div>
        </div>
    </div>
</x-app-layout>
