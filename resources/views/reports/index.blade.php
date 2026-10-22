@php
    $durationLabel = static function ($hours) {
        if ($hours <= 0) return '0 min';
        if ($hours < 1 / 60) return 'Menos de 1 min';
        if ($hours < 1) return number_format($hours * 60, 0, ',', '.').' min';
        if ($hours < 24) return number_format($hours, 1, ',', '.').' h';
        return number_format($hours / 24, 1, ',', '.').' dias';
    };
    $stageMaximum = max(0.000001, $stageDurations->max() ?? 0);
@endphp
<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Indicadores de execução</h2></x-slot>
    <div class="execution-report mx-auto max-w-7xl stack-6 px-4 py-8">
        <div class="report-intro">
            <div><p class="report-eyebrow">Visão consolidada</p><h3>Acompanhe o caminho das ideias</h3><p class="report-muted">Contribuições, participação e resultados no período selecionado.</p></div>
            <a class="apple-button--light" href="{{ route('reports.index') }}">Ver relatório por ciclo →</a>
        </div>

        <form class="apple-card report-panel" method="GET" x-data="{ updating: false }" @submit="updating = true" :aria-busy="updating">
            <div class="report-section-heading"><div><h3>Filtrar indicadores</h3><p class="report-muted">As datas consideram o envio das propostas.</p></div><a href="{{ route('reports.index', ['view' => 'general']) }}">Limpar filtros</a></div>
            <input type="hidden" name="view" value="general">
            <div class="report-filters">
                <x-workflow-field name="from" label="De" type="date" :required="false" :value="request('from')" />
                <x-workflow-field name="to" label="Até" type="date" :required="false" :value="request('to')" />
                <label>Setor<select name="sector"><option value="">Todos os setores</option>@foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(request('sector') == $sector->id)>{{ $sector->name }}</option>@endforeach</select></label>
                <label>Status<select name="status"><option value="">Todos os status</option>@foreach (\App\Models\Idea::LABELS as $status => $label)<option value="{{ $status }}" @selected(request('status') == $status)>{{ $label }}</option>@endforeach</select></label>
                <x-workflow-field name="category" label="Categoria" :required="false" :value="request('category')" />
                <button class="apple-button" :disabled="updating" x-text="updating ? 'Atualizando…' : 'Aplicar filtros'">Aplicar filtros</button>
            </div>
        </form>

        <div class="report-stats">
            <x-stat-card label="Ideias recebidas" :value="$total" icon="idea" tone="brand" description="Propostas no recorte selecionado" />
            <x-stat-card label="Resultados validados" :value="$implemented" icon="check" tone="emerald" :index="1" :description="($total ? number_format($implemented / $total * 100, 1, ',', '.') : '0').'% das propostas recebidas'" />
            <x-stat-card label="Tempo médio até execução" :value="$durations->isNotEmpty() ? $durationLabel($durations->avg() * 24) : '—'" icon="chart" tone="violet" :index="2" :description="$durations->count().' implementações com conclusão registrada'" />
            <x-stat-card label="Reconhecimentos aprovados" :value="'R$ '.number_format($awards->sum('award_amount'), 2, ',', '.')" icon="check" tone="amber" :index="3" :description="$awards->count().' reconhecimentos aprovados'" />
        </div>

        <div class="report-grid">
            <section class="apple-card report-panel">
                <div class="report-section-heading"><div><p class="report-eyebrow">Distribuição</p><h3>Onde estão as ideias?</h3></div><span class="report-chip">{{ $total }} no total</span></div>
                <div class="report-bar-list">
                    @forelse ($distribution as $status => $count)
                        <div class="report-bar-row" style="--row-delay: {{ $loop->index * 45 }}ms">
                            <div class="report-bar-caption"><span>{{ \App\Models\Idea::LABELS[$status] ?? $status }}</span><strong>{{ $count }} <small>· {{ $total ? number_format($count / $total * 100, 0, ',', '.') : 0 }}%</small></strong></div>
                            <div class="report-bar-track" aria-hidden="true"><span class="report-bar-fill" style="width: {{ $total ? $count / $total * 100 : 0 }}%"></span></div>
                        </div>
                    @empty <p class="report-empty">Nenhuma proposta corresponde aos filtros. Experimente ampliar o período.</p> @endforelse
                </div>
            </section>
            <section class="apple-card report-panel report-evolution">
                <div class="report-section-heading"><div><p class="report-eyebrow">Ao longo do tempo</p><h3>Evolução das contribuições</h3></div></div>
                <x-metric-chart :values="$monthly" label="Propostas recebidas por mês" unit="propostas" />
                @if(count($monthly))<details class="report-chart-data"><summary>Consultar valores por mês</summary><dl>@foreach($monthly as $month => $count)<div><dt>{{ \Illuminate\Support\Carbon::parse($month.'-01')->format('m/Y') }}</dt><dd>{{ $count }} propostas</dd></div>@endforeach</dl></details>@endif
            </section>
        </div>

        <section class="apple-card report-panel">
            <div class="report-section-heading"><div><p class="report-eyebrow">Envolvimento da equipe</p><h3>Participação e abrangência</h3></div></div>
            <div class="report-participation"><div><strong>{{ $participation }}</strong><span>Pessoas que votaram</span></div><div><strong>{{ $voteCount }}</strong><span>Votos válidos</span></div><div><strong>{{ $benefitedSectors }}</strong><span>Setores beneficiados nas propostas</span></div></div>
            <p class="report-muted">Cada pessoa é contada uma vez neste resumo, mesmo que participe de vários ciclos.</p>
            <h4 class="report-subheading">Participação por ciclo</h4>
            <p class="report-muted">Votos válidos nas propostas filtradas. Uma pessoa pode votar em várias ideias; estes totais não representam uma taxa histórica de elegibilidade.</p>
            <div class="report-table-scroll" role="region" aria-label="Participação por ciclo" tabindex="0"><table class="report-table"><thead><tr><th scope="col">Ciclo</th><th scope="col">Situação</th><th scope="col">Pessoas</th><th scope="col">Votos válidos</th></tr></thead><tbody>@forelse($cycleParticipation as $entry)<tr><th scope="row">{{ $entry['name'] }}</th><td><span class="report-chip">{{ ['draft'=>'Rascunho','open'=>'Aberto','closed'=>'Apurado','cancelled'=>'Cancelado'][$entry['status']] ?? $entry['status'] }}</span></td><td>{{ $entry['people'] }}</td><td>{{ $entry['votes'] }}</td></tr>@empty<tr><td colspan="4" class="report-empty">Sem ciclos para as propostas filtradas.</td></tr>@endforelse</tbody></table></div>
        </section>

        <section class="apple-card report-panel">
            <div class="report-section-heading"><div><p class="report-eyebrow">Ritmo de execução</p><h3>Tempo médio por etapa</h3><p class="report-muted">Barras comparadas à etapa mais demorada, com tempo em minutos, horas ou dias.</p></div></div>
            <div class="report-stage-grid">@forelse($stageDurations as $stage => $hours)<div class="report-stage report-bar-row" style="--row-delay: {{ $loop->index * 45 }}ms"><div class="report-bar-caption"><span>{{ \App\Models\Idea::LABELS[$stage] ?? $stage }}</span><strong>{{ $durationLabel($hours) }}</strong></div><div class="report-bar-track" aria-hidden="true"><span class="report-bar-fill" style="width: {{ $hours / $stageMaximum * 100 }}%"></span></div></div>@empty<p class="report-empty">Histórico insuficiente para calcular. Os tempos aparecem após uma mudança de etapa.</p>@endforelse</div>
            <p class="report-muted report-footnote">Considera etapas com próxima transição registrada. Solicitações e respostas na mesma etapa não reiniciam o prazo.</p>
        </section>

        <section class="apple-card report-panel">
            <div class="report-section-heading"><div><p class="report-eyebrow">Resultados comprovados</p><h3>Variação dos indicadores validados</h3><p class="report-muted">Variação = depois menos antes. Um valor negativo indica redução, não necessariamente piora.</p></div></div>
            <div class="report-table-scroll" role="region" aria-label="Indicadores validados" tabindex="0"><table class="report-table"><thead><tr><th scope="col">Setor de origem</th><th scope="col">Indicador</th><th scope="col">Período</th><th scope="col">Variação</th><th scope="col">Medições</th></tr></thead><tbody>@forelse($benefits as $benefit)<tr><th scope="row">{{ $benefit['sector'] }}</th><td>{{ $benefit['indicator'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($benefit['from'])->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($benefit['to'])->format('d/m/Y') }}</td><td class="report-number">{{ number_format($benefit['difference'],2,',','.') }} {{ $benefit['unit'] }}</td><td>{{ $benefit['count'] }}</td></tr>@empty<tr><td colspan="5" class="report-empty">Ainda não há medições validadas para este recorte.</td></tr>@endforelse</tbody></table></div>
            <p class="report-muted report-footnote">Somente medições do mesmo setor, indicador, unidade e período são agrupadas. Confira se são independentes antes de interpretar como benefício total da empresa.</p>
        </section>

        <section class="apple-card report-panel">
            <div class="report-section-heading"><div><h3>Propostas neste recorte</h3><p class="report-muted">Publicações e situação da validação dos resultados.</p></div></div>
            <div class="report-table-scroll" role="region" aria-label="Propostas filtradas" tabindex="0"><table class="report-table"><thead><tr><th scope="col">Código</th><th scope="col">Publicação</th><th scope="col">Status</th><th scope="col">Validação</th></tr></thead><tbody>@forelse ($ideas as $idea)<tr><td class="report-code">{{ $idea->idea_code }}</td><th scope="row">{{ $idea->publication['title'] ?? 'Publicação pendente' }}</th><td>{{ \App\Models\Idea::LABELS[$idea->status] ?? $idea->status }}</td><td>{{ $idea->result?->validated_at?->format('d/m/Y') ?? 'Pendente' }}</td></tr>@empty<tr><td colspan="4" class="report-empty">Nenhuma proposta encontrada com estes filtros.</td></tr>@endforelse</tbody></table></div>
            <div class="mt-4">{{ $ideas->links() }}</div>
        </section>
        <div class="report-export"><div><h3>Leve os dados para sua análise</h3><p class="report-muted">CSV com indicadores validados, separados por unidade. Sem estimativas, autoria ou votos individuais.</p></div><a class="apple-button" href="{{ route('reports.export', request()->query()) }}">Exportar CSV</a></div>
    </div>
</x-app-layout>
