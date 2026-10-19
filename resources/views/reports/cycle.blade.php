<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Relatório por ciclo</h2><p class="mt-2 text-slate-600">Da participação à bonificação: confira uma rodada de cada vez.</p></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
        <form method="GET" class="apple-card flex flex-wrap items-end gap-4 p-5">
            <label class="min-w-0 flex-1" for="report-cycle">Escolha o ciclo<select id="report-cycle" name="cycle" class="mt-2 block w-full rounded-lg" required>
                @forelse($cycles as $option)<option value="{{ $option->id }}" @selected($cycle?->id === $option->id)>{{ $option->name }} — {{ ['draft'=>'Rascunho','open'=>'Aberto/programado','closed'=>'Apurado','cancelled'=>'Cancelado'][$option->status] ?? $option->status }}</option>@empty<option value="">Nenhum ciclo cadastrado</option>@endforelse
            </select></label>
            <button class="apple-button" @disabled($cycles->isEmpty())>Ver relatório</button>
            <a class="apple-button--light" href="{{ route('reports.index',['view'=>'general']) }}">Indicadores de execução</a>
        </form>
        @php($canManage = true)
        @php($reportMode = true)
        @if($cycle)
            @include('cycles.partials.overview')
            @if($cycleReport['closed'])
                <div class="flex flex-wrap gap-3"><a class="apple-button" href="{{ route('reports.cycle-export',['cycle'=>$cycle->id]) }}">Exportar resultado do ciclo</a><a class="apple-button--light" href="{{ route('cycles.show',$cycle) }}">Abrir ciclo e registrar decisões</a></div>
                @include('cycles.partials.ranking')
            @else
                <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">{{ $cycle->status === 'draft' ? 'Ciclo em preparação' : ($cycle->status === 'cancelled' ? 'Ciclo cancelado' : 'Resultado ainda não apurado') }}</h3><p>{{ $cycle->status === 'cancelled' ? 'O cancelamento preserva o histórico. Crie outro ciclo para realizar uma nova rodada.' : ($cycle->status === 'draft' ? 'Abra o ciclo, escolha as ideias e confirme a programação. A classificação aparecerá após a votação e a apuração.' : 'Após o fim do período, abra o ciclo e use Encerrar e apurar. A classificação ficará disponível aqui.') }}</p><a class="apple-button--light" href="{{ route('cycles.show',$cycle) }}">Abrir ciclo</a></section>
            @endif
            @include('cycles.partials.finance')
        @else
            <section class="apple-card space-y-3 p-8"><h3 class="text-xl font-bold">Seu primeiro relatório começa com um ciclo</h3><p>Depois de criar uma rodada e incluir as ideias, a participação aparecerá aqui. A classificação será exibida após a apuração.</p><a class="apple-button" href="{{ route('cycles.create') }}">Novo ciclo</a></section>
        @endif
    </div>
</x-app-layout>
