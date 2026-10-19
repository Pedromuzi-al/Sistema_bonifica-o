<section class="cycle-overview apple-card space-y-4 p-6" aria-label="Resumo do ciclo">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><p class="text-sm text-slate-500">{{ $cycle->isSelection() ? 'Seleção de ideias' : 'Reconhecimento por resultados' }}</p><h3 class="mt-1 text-2xl font-bold">{{ $cycle->name }}</h3></div>
        <span class="apple-pill">{{ match($cycle->status) { 'closed'=>'Apurado', 'draft'=>'Rascunho', 'cancelled'=>'Cancelado', default => now()->lt($cycle->starts_at) ? 'Programado' : (now()->gte($cycle->ends_at) ? 'Aguardando apuração' : 'Em votação') } }}</span>
    </div>
    <p class="text-sm text-slate-600">{{ $cycle->local_starts_at->format('d/m/Y H:i') }} → {{ $cycle->local_ends_at->format('d/m/Y H:i') }} · {{ config('innovation.cycle_timezone') }}</p>
    @if($cycle->status === 'cancelled')<p class="text-sm">Este ciclo foi cancelado e não terá apuração.</p>@elseif(!$cycleReport['closed'])<p class="text-sm">A classificação aparece após o administrador encerrar e apurar o ciclo. As contagens abaixo não indicam quais ideias estão na frente.</p>@endif
</section>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores do ciclo">
    <x-stat-card label="Ideias no ciclo" :value="$cycleReport['idea_count']" icon="idea" tone="brand" description="Propostas desta rodada" />
    <x-stat-card label="Pessoas participantes" :value="$cycleReport['people']" icon="user" tone="sky" description="Cada pessoa contada uma vez" :index="1" />
    <x-stat-card label="Votos válidos" :value="$cycleReport['vote_count']" icon="vote" tone="violet" description="Uma pessoa pode votar em várias ideias" :index="2" />
    @if($cycleReport['closed'] && $cycle->isSelection())
        <x-stat-card label="Ideias selecionadas" :value="$cycleReport['selected']" icon="check" tone="emerald" description="Decisão da gestão após a apuração" :index="3" />
    @else
        <x-stat-card label="Classificação" :value="$cycleReport['closed'] ? 'Apurada' : 'Aguardando'" icon="chart" tone="amber" description="Não há ranking parcial durante a votação" :index="3" />
    @endif
</div>
