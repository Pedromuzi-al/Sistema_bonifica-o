<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel de avaliação</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <section class="apple-card mb-6 grid gap-3 p-4 sm:grid-cols-3">@foreach (['submitted','triage','evaluation','awaiting_information','approved','implementation','validation'] as $status)<p>{{ \App\Models\Idea::LABELS[$status] }}: <strong>{{ $summary[$status] ?? 0 }}</strong></p>@endforeach</section>
            <section class="apple-card mb-6 space-y-2 p-4"><h3 class="font-bold">Prazos vencidos ou nos próximos 7 dias</h3>@forelse ($deadlines as $item)<a class="block text-sky-700" href="{{ route('ideas.show', $item) }}">{{ $item->idea_code }} — {{ $item->implementationPlan->due_date->format('d/m/Y') }} {{ $item->implementationPlan->due_date->isPast() ? '(vencido)' : '' }}</a>@empty<p>Nenhum prazo próximo.</p>@endforelse</section>
            <form method="GET" class="apple-card mb-6 grid gap-3 p-4 sm:grid-cols-3">
                <label>Status<select name="status" class="block w-full rounded-lg"><option value="">Todos</option>@foreach (\App\Models\Idea::LABELS as $status=>$label)<option value="{{ $status }}" @selected(request('status')==$status)>{{ $label }}</option>@endforeach</select></label>
                <label>Setor<select name="sector" class="block w-full rounded-lg"><option value="">Todos</option>@foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(request('sector')==$sector->id)>{{ $sector->name }}</option>@endforeach</select></label>
                <label>Responsável<select name="owner" class="block w-full rounded-lg"><option value="">Todos</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected(request('owner')==$user->id)>{{ $user->name }}</option>@endforeach</select></label>
                <x-workflow-field name="from" label="De" type="date" :required="false" :value="request('from')" /><x-workflow-field name="to" label="Até" type="date" :required="false" :value="request('to')" /><button class="apple-button">Filtrar</button>
            </form>
            <div class="bg-white rounded-xl shadow overflow-x-auto">
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
                                <td class="px-4 py-3 text-sm text-gray-700">{{ \App\Models\Idea::LABELS[$idea->status] ?? $idea->status }}<p>Prazo: {{ $idea->implementationPlan?->due_date?->format('d/m/Y') ?? 'Sem plano' }}</p></td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $idea->visibleAuthorName(auth()->user()) }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('ideas.show', $idea) }}" class="font-medium text-sky-600 hover:text-sky-800">Detalhes</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $ideas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
