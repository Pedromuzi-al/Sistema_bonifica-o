<section class="apple-card overflow-hidden" aria-labelledby="cycle-ranking-title">
    <div class="stack-2 p-6">
        <h3 id="cycle-ranking-title" class="text-xl font-bold">Classificação das ideias</h3>
        <p class="text-sm text-slate-600">A pontuação vai de 0 a 100. Classificação, decisão da gestão e bonificação são etapas distintas.</p>
        @if($cycle->isSelection())<p class="text-sm">{{ $cycleReport['pending_decisions'] }} ideia(s) aguardando decisão da gestão.</p>@endif
    </div>
    <div class="overflow-x-auto" role="region" aria-label="Resultado da votação por ideia" tabindex="0">
        <table class="cycle-result-table w-full text-left text-sm">
            <thead><tr><th scope="col">Posição</th><th scope="col">Ideia</th><th scope="col">Pontuação</th><th scope="col">Votos válidos</th><th scope="col">Decisão</th><th scope="col">Bonificação</th><th scope="col">Detalhes</th></tr></thead>
            <tbody>
                @forelse($cycleReport['entries'] as $result)
                <tr class="cycle-result-row" style="--result-delay: {{ min($loop->index * 35, 210) }}ms">
                    <td><span class="result-rank">{{ $result['score'] !== null ? $result['rank'].'º' : '—' }}</span></td>
                    <th scope="row"><span class="block font-semibold">{{ $result['title'] }}</span><span class="mt-1 block text-xs font-normal text-slate-500">{{ $result['code'] }}</span></th>
                    <td>
                        @if($result['score'] !== null)
                            <span class="font-bold">{{ number_format($result['score'], 2, ',', '.') }}/100</span>
                            <div class="result-score-track" aria-hidden="true"><span class="result-score-fill" style="width: {{ max(0, min(100, (float)$result['score'])) }}%"></span></div>
                        @else<span class="text-slate-500">Votos insuficientes</span>@endif
                    </td>
                    <td>{{ $result['votes'] ?? 0 }}</td>
                    <td><span class="result-decision" data-state="{{ $result['decision'] ?? 'pending' }}">{{ !$cycle->isSelection() ? 'Reconhecimento por resultados' : match($result['decision']) { 'selected'=>'Selecionada para avançar', 'bank'=>'No banco de ideias', default=>'Aguardando decisão' } }}</span></td>
                    <td>
                        @forelse($result['awards'] as $award)
                            <span class="block font-semibold">R$ {{ number_format($award->award_amount,2,',','.') }}</span>
                            <span class="block text-xs text-slate-500">{{ $award->rejected_at ? 'Não aprovada' : ($award->published_at ? 'Divulgada' : ($award->approved_at ? 'Aprovada · falta divulgar' : 'Proposta · falta aprovar')) }}</span>
                        @empty<span class="text-slate-500">{{ $canManage ? 'Sem bonificação proposta' : 'Nenhuma bonificação divulgada' }}</span>@endforelse
                    </td>
                    <td>
                        <a class="result-details-link text-sky-700 underline" href="{{ ($reportMode ?? false) ? route('cycles.show',$cycle).'#cycle-entry-'.$result['id'] : '#cycle-entry-'.$result['id'] }}" @unless($reportMode ?? false) x-data @click="document.getElementById('cycle-entry-{{ $result['id'] }}').open = true" @endunless aria-label="Ver detalhes da ideia {{ $result['code'] }}">Ver detalhes</a>
                    </td>
                </tr>
                @empty<tr><td colspan="7" class="p-6">Nenhuma ideia registrada neste ciclo.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</section>
