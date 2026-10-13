<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ideias e propostas</h2>
            <a href="{{ route('ideas.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nova ideia</a>
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
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ ucfirst($idea->status) }}</span>
                        </div>

                        <p class="mt-4 text-gray-700">{{ Str::limit($idea->solution_description, 240) }}</p>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
                            <div>Autor: {{ $idea->visibleAuthorName(auth()->user()) }}</div>
                            <div>{{ $idea->created_at->format('d/m/Y') }}</div>
                        </div>

                        <div class="mt-5">
                            <a href="{{ route('ideas.show', $idea) }}" class="text-indigo-600 font-medium hover:text-indigo-800">Abrir detalhe</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
