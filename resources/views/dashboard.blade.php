<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard corporativo
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Total de ideias</p>
                    <p class="text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Em avaliação</p>
                    <p class="text-3xl font-bold text-amber-600">{{ $stats['in_evaluation'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Aprovadas</p>
                    <p class="text-3xl font-bold text-emerald-600">{{ $stats['approved'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Em implementação</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $stats['implementation'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Votações abertas</p>
                    <p class="text-3xl font-bold text-violet-600">{{ $stats['open_votes'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow p-6 xl:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">Ideias recentes</h3>
                        <a href="{{ route('ideas.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todas</a>
                    </div>

                    <div class="space-y-4">
                        @foreach ($recentIdeas as $idea)
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $idea->title }}</p>
                                        <p class="text-sm text-gray-500">{{ $idea->originSector?->name }} · {{ $idea->visibleAuthorName(auth()->user()) }}</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                        {{ ucfirst($idea->status) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ Str::limit($idea->problem_description, 140) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Notificações</h3>
                    <div class="space-y-3">
                        @forelse ($notifications as $notification)
                            <div class="border-l-4 border-indigo-500 pl-3">
                                <p class="font-medium text-slate-700">{{ $notification->title }}</p>
                                <p class="text-sm text-gray-600">{{ $notification->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Nenhuma notificação recente.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-slate-800">Atalhos</h3>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('ideas.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Cadastrar ideia</a>
                    <a href="{{ route('ideas.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-4 py-2 rounded-lg text-sm font-medium">Minhas ideias</a>
                    <a href="{{ route('evaluation.index') }}" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Acompanhar avaliação</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
