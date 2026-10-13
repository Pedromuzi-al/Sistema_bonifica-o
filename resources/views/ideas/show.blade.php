<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalhes da ideia</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex flex-col md:flex-row md:justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500">{{ $idea->idea_code }}</p>
                        <h3 class="text-2xl font-bold text-slate-900">{{ $idea->title }}</h3>
                    </div>
                    <x-idea-status-badge :status="$idea->status" />
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                    <div><span class="font-semibold text-gray-800">Autor:</span> {{ $idea->visibleAuthorName(auth()->user()) }}</div>
                    <div><span class="font-semibold text-gray-800">Setor:</span> {{ $idea->originSector?->name }}</div>
                    <div><span class="font-semibold text-gray-800">Criada:</span> {{ $idea->created_at->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 bg-white rounded-xl shadow p-6 space-y-5">
                    <div>
                        <h4 class="font-semibold text-slate-800">Problema identificado</h4>
                        <p class="mt-2 text-gray-700">{{ $idea->problem_description }}</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-800">Solução proposta</h4>
                        <p class="mt-2 text-gray-700">{{ $idea->solution_description }}</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-800">Resultados esperados</h4>
                        <p class="mt-2 text-gray-700">{{ $idea->expected_results }}</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-800">Recursos necessários</h4>
                        <p class="mt-2 text-gray-700">{{ $idea->resources_needed }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h4 class="font-semibold text-slate-800 mb-4">Ações</h4>

                    @if (auth()->user()->can('vote', $idea))
                        <form method="POST" action="{{ route('ideas.vote', $idea) }}" class="space-y-4">
                            @csrf
                            @php($cycle = App\Models\VotingCycle::where('status', 'open')->where('starts_at', '<=', now())->where('ends_at', '>=', now())->first())
                            @if ($cycle)
                                @foreach ($cycle->criteria as $criterion)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">{{ $criterion->name }}</label>
                                        <input type="number" name="criteria_{{ $criterion->id }}" min="0" max="5" step="0.1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="3.0">
                                    </div>
                                @endforeach
                                <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500" data-permission="true" data-permission-message="Você não tem permissão para votar nesta ideia.">Registrar voto</button>
                            @else
                                <p class="text-sm text-gray-500">Nenhum ciclo de votação aberto.</p>
                            @endif
                        </form>
                    @else
                        <button type="button" data-permission="false" data-permission-message="Você não tem permissão para votar nesta ideia." class="w-full rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-sky-900">Registrar voto</button>
                    @endif

                    @if (auth()->user()->can('approve', $idea))
                        <form method="POST" action="{{ route('ideas.approve', $idea) }}" class="mt-6 space-y-3">
                            @csrf
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="approved">Aprovar</option>
                                <option value="rejected">Rejeitar</option>
                                <option value="implementation">Em implementação</option>
                                <option value="implemented">Implementada</option>
                                <option value="completed">Concluída</option>
                            </select>
                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Justificativa"></textarea>
                            <button type="submit" class="w-full rounded-full bg-[#0faddf] px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-[#0b96c2]" data-permission="true" data-permission-message="Você não tem permissão para alterar o status desta ideia.">Atualizar status</button>
                        </form>
                    @else
                        <button type="button" data-permission="false" data-permission-message="Você não tem permissão para alterar o status desta ideia." class="mt-6 w-full rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-sky-900">Atualizar status</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
