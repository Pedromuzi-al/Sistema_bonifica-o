<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Votações e reconhecimentos</h2></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h3 class="text-xl font-bold">Ciclos cadastrados</h3><p class="mt-1 text-sm text-slate-600">Acompanhe os períodos de votação, as propostas e os resultados.</p></div>
            @if($canManage)<a class="apple-button gap-2" href="{{ route('cycles.create') }}"><span aria-hidden="true">＋</span> Novo ciclo</a>@endif
        </div>
        @if($cycles->isEmpty())
            <section class="apple-card space-y-3 p-8 text-center">
                <h4 class="text-lg font-semibold">{{ $canManage ? 'Nenhum ciclo cadastrado ainda.' : 'Nenhum ciclo disponível no momento.' }}</h4>
                <p class="text-sm text-slate-600">{{ $canManage ? 'Clique em Novo ciclo para criar o primeiro período de votação.' : 'Os ciclos aparecerão aqui quando forem abertos pela administração.' }}</p>
            </section>
        @else
            <section class="apple-card overflow-hidden">
                <div class="overflow-x-auto" role="region" aria-label="Listagem dos ciclos" tabindex="0">
                    <table class="cycle-list w-full text-left text-sm">
                        <caption class="sr-only">Ciclos de votação cadastrados, período e situação</caption>
                        <thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr>
                            <th scope="col" class="px-5 py-4">Ciclo</th><th scope="col" class="px-5 py-4">Tipo</th><th scope="col" class="px-5 py-4">Período da votação</th><th scope="col" class="px-5 py-4">Situação</th><th scope="col" class="px-5 py-4">Ações</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($cycles as $cycle)
                                <tr>
                                    <th scope="row" class="px-5 py-4 font-semibold"><a class="text-sky-700 underline" href="{{ route('cycles.show',$cycle) }}">{{ $cycle->name }}</a></th>
                                    <td class="px-5 py-4">{{ $cycle->isSelection() ? 'Seleção de ideias' : 'Reconhecimento por resultados' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ $cycle->local_starts_at->format('d/m/Y H:i') }}<br><span class="text-slate-500">até {{ $cycle->local_ends_at->format('d/m/Y H:i') }}</span></td>
                                    <td class="px-5 py-4"><span class="apple-pill cycle-status whitespace-nowrap">{{ ['draft'=>'Rascunho','open'=>'Aberto / programado','closed'=>'Encerrado','cancelled'=>'Cancelado'][$cycle->status] ?? $cycle->status }}</span></td>
                                    <td class="px-5 py-4"><a class="text-sky-700 underline" href="{{ route('cycles.show',$cycle) }}" aria-label="Ver ciclo {{ $cycle->name }}">Ver ciclo</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $cycles->links() }}
        @endif
    </div>
</x-app-layout>
