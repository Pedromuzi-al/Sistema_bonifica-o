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
            <div class="grid gap-6">
                @foreach ($ideas as $idea)
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">{{ $idea->idea_code }} · {{ $idea->originSector?->name }}</p>
                                <h3 class="text-xl font-bold text-slate-900">{{ $idea->title }}</h3>
                            </div>
                            <x-idea-status-badge :status="$idea->status" />
                        </div>

                        <p class="mt-4 text-gray-700">{{ Str::limit($idea->solution_description, 240) }}</p>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Autor: {{ $idea->visibleAuthorName(auth()->user()) }}</div>
                            <div>{{ $idea->created_at->format('d/m/Y') }}</div>
                        </div>

                        <div class="mt-5">
                            <a href="{{ route('ideas.show', $idea) }}" class="font-medium text-sky-600 hover:text-sky-800">Abrir detalhe</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
