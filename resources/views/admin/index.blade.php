<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administração</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Total recebidas</p>
                    <p class="text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Aprovadas</p>
                    <p class="text-3xl font-bold text-emerald-600">{{ $stats['approved'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Implementadas</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $stats['implemented'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Concluídas</p>
                    <p class="text-3xl font-bold text-violet-600">{{ $stats['completed'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-3">Setores</h3>
                    @foreach ($sectors as $sector)
                        <div class="flex justify-between items-center border-b py-2 last:border-b-0">
                            <span class="text-gray-700">{{ $sector->name }}</span>
                            <span class="text-sm text-gray-500">{{ $sector->ideas_as_origin_count }} ideias</span>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-3">Últimas ideias</h3>
                    @foreach ($ideas as $idea)
                        <div class="border-b py-3 last:border-b-0">
                            <div class="font-medium text-slate-800">{{ $idea->title }}</div>
                            <div class="text-sm text-gray-500">{{ $idea->status }} · {{ $idea->visibleAuthorName(auth()->user()) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
