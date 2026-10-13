<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel de avaliação</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Ideia</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Autor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($ideas as $idea)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $idea->title }}</div>
                                    <div class="text-sm text-gray-500">{{ $idea->idea_code }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($idea->status) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $idea->visibleAuthorName(auth()->user()) }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('ideas.show', $idea) }}" class="font-medium text-sky-600 hover:text-sky-800">Detalhes</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
