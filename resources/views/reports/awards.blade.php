<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Bonificações por ciclo</h2></x-slot>
    <div class="execution-report award-report mx-auto max-w-7xl stack-6 px-4 py-8">
        <div class="report-intro"><div><p class="report-eyebrow">Exclusivo da administração</p><h3>Ideias, pessoas e reconhecimentos</h3><p class="report-muted">Confira a classificação e a destinação dos valores, com cada rodada separada.</p></div><a class="apple-button--light" href="{{ route('reports.index') }}">Voltar aos relatórios</a></div>
        <div class="report-stats">
            <x-stat-card label="Ciclos apurados" :value="$summary['cycles']" icon="vote" tone="brand" description="No recorte selecionado" />
            <x-stat-card label="Ideias nas rodadas" :value="$summary['ideas']" icon="idea" :index="1" description="Participações; uma ideia pode retornar em outro ciclo" />
            <x-stat-card label="Valor aprovado" :value="'R$ '.number_format($summary['approved'], 2, ',', '.')" tone="emerald" icon="check" :index="2" description="Inclui valores já divulgados" />
            <x-stat-card label="Aguardando aprovação" :value="'R$ '.number_format($summary['pending'], 2, ',', '.')" tone="amber" icon="chart" :index="3" description="Propostas de bonificação ainda pendentes" />
        </div>
        <form method="GET" class="apple-card report-panel" x-data="{ updating: false }" @submit="updating = true" :aria-busy="updating">
            <div class="report-section-heading"><div><h3>Filtros</h3><p class="report-muted">Período de encerramento da votação, no horário de {{ config('innovation.cycle_timezone') }}. Resumos consideram todos os ciclos filtrados.</p></div></div>
            <div class="report-filters">
                <label>Ciclo<select name="cycle"><option value="">Todos os ciclos apurados</option>@foreach($options as $option)<option value="{{ $option->id }}" @selected(request('cycle') == $option->id)>{{ $option->name }}</option>@endforeach</select></label>
                <x-workflow-field name="from" label="Encerramento a partir de" type="date" :required="false" :value="request('from')" />
                <x-workflow-field name="to" label="Encerramento até" type="date" :required="false" :value="request('to')" />
            </div>
            <div class="mt-5 flex flex-wrap justify-end gap-3"><a class="apple-button--light" href="{{ route('reports.awards') }}">Limpar filtros</a><button class="apple-button" :disabled="updating" x-text="updating ? 'Atualizando…' : 'Aplicar filtros'">Aplicar filtros</button></div>
        </form>
        <p class="report-muted">A classificação usa a pontuação da metodologia; a ideia com mais votos pode ter outra posição. Aprovação e divulgação são registros de reconhecimento: o sistema não confirma pagamento bancário.</p>
        @forelse($groups as $group)
            @php($cycle = $group['cycle'])
            @php($report = $group['report'])
            <section class="apple-card report-panel award-cycle" aria-labelledby="award-cycle-{{ $cycle->id }}">
                <div class="report-section-heading"><div><p class="report-eyebrow">Ciclo #{{ $cycle->id }} · Apurado</p><h3 id="award-cycle-{{ $cycle->id }}">{{ $cycle->name }}</h3><p class="report-muted">{{ $cycle->local_starts_at->format('d/m/Y H:i') }} a {{ $cycle->local_ends_at->format('d/m/Y H:i') }}</p></div><a class="apple-button--light" href="{{ route('reports.index', ['cycle' => $cycle->id]) }}">Abrir resultado</a></div>
                <div class="award-cycle-summary"><span><strong>{{ $report['idea_count'] }}</strong> ideias</span><span><strong>{{ $report['vote_count'] }}</strong> votos válidos</span><span>Aprovado <strong>R$ {{ number_format($report['approved_amount'], 2, ',', '.') }}</strong></span><span>Divulgado <strong>R$ {{ number_format($report['published_amount'], 2, ',', '.') }}</strong></span></div>
                <div class="award-leader"><x-sidebar-icon name="vote" /><div><h4>Maior número de votos válidos</h4>@forelse($report['most_voted'] as $leader)<p>{{ $leader['title'] }} <strong>· {{ $leader['votes'] }} votos</strong>@if($report['most_voted']->count() > 1) <span class="report-chip">Empate em votos</span>@endif</p>@empty<p class="report-muted">Não houve votos válidos neste ciclo.</p>@endforelse</div></div>
                <p class="report-muted">Uma linha por destinatário e bonificação. Nas ideias em grupo, o valor individual usa a divisão registrada naquela rodada.</p>
                <div class="report-table-scroll" role="region" aria-label="Destinatários e classificação do ciclo {{ $cycle->id }}" tabindex="0"><table class="report-table award-table"><thead><tr><th scope="col">Posição</th><th scope="col">Ideia</th><th scope="col">Pontuação</th><th scope="col">Votos válidos</th><th scope="col">Destinatário</th><th scope="col">Valor individual</th><th scope="col">Situação</th></tr></thead><tbody>
                    @forelse($report['rows'] as $row)
                    <tr class="cycle-result-row"><td><span class="result-rank">{{ $row['entry']['score'] !== null ? $row['entry']['rank'].'º' : '—' }}</span></td><th scope="row">{{ $row['entry']['title'] }}<span class="report-muted block">{{ $row['entry']['code'] }}</span></th><td>{{ $row['entry']['score'] !== null ? number_format($row['entry']['score'], 2, ',', '.').'/100' : 'Votos insuficientes' }}</td><td>{{ $row['entry']['votes'] ?? 0 }}</td><td>{{ $row['recipient'] }}</td><td class="report-number">{{ $row['amount'] !== null ? 'R$ '.number_format($row['amount'], 2, ',', '.') : '—' }}</td><td><span class="report-chip">{{ $row['state'] }}</span></td></tr>
                    @empty<tr><td colspan="7" class="report-empty">Nenhuma ideia registrada neste ciclo.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        @empty<section class="apple-card report-panel"><h3 class="font-bold">Nenhum ciclo apurado neste recorte</h3><p class="report-empty">Finalize e apure uma votação para consultar a classificação e os destinatários. Se houver filtros, tente ampliar o período.</p></section>@endforelse
        {{ $cycles->links() }}
    </div>
</x-app-layout>
