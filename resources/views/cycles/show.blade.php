<x-app-layout>
<x-slot name="header"><h2 class="text-2xl font-bold">{{ $cycle->name }}</h2></x-slot>
<div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
    @php($config = $cycle->frozen_configuration ?? $cycle->weight_configuration)
    <details class="apple-card space-y-3 p-6"><summary class="cursor-pointer text-xl font-bold">Regras do ciclo {{ $cycle->frozen_configuration ? '(confirmadas)' : '(rascunho)' }}</summary>
        <p>{{ $cycle->starts_at->format('d/m/Y H:i') }} a {{ $cycle->ends_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</p>
        @foreach ($config['weights'] ?? [] as $dimension=>$weight)<p>{{ ['real_results'=>'Resultados reais','technical'=>'Avaliação técnica','votes'=>'Votação'][$dimension] }}: {{ $weight }}%</p>@endforeach
        <p>Nota técnica e votação: (nota de 1 a 5 − 1) ÷ 4 × 100, ponderada pelos critérios. Abstenções habilitadas redistribuem o peso entre os critérios respondidos de cada voto.</p>
        @foreach ($config['result_indicators'] ?? [] as $rubric)<p>{{ $rubric['indicator'] }} — {{ $rubric['unit'] }}; {{ ['increase'=>'aumento','decrease'=>'redução','qualitative'=>'escala qualitativa'][$rubric['direction']] }}; meta {{ $rubric['target'] }}. Pontuação = ganho ÷ meta × 100, limitada entre 0 e 100.</p>@endforeach
        <p>Mínimo de votos: {{ $config['minimum_votes'] ?? 'Não configurado' }}. Sem o mínimo, não há nota final nem elegibilidade para premiação.</p>
        <p>Desempate: {{ implode(' → ', array_map(fn ($key) => ['real_results'=>'Resultados reais','technical'=>'Avaliação técnica','votes'=>'Votação','idea_code'=>'Código da ideia'][$key], $config['tie_breakers'] ?? ['real_results','technical','votes','idea_code'])) }}.</p>
        @foreach($config['categories'] ?? [] as $band)<p>{{ $band['name'] }}: {{ $band['minimum_score'] }} a {{ $band['maximum_score'] }} pontos; até {{ $band['max_awards'] }} ideias; R$ {{ number_format($band['reward_amount'],2,',','.') }} por ideia.</p>@endforeach
        <p>Reconhecimento principal (usado quando não há faixas): {{ $config['category'] ?? 'Não configurada' }}; mínimo {{ $config['minimum_score'] ?? '—' }}/100; até {{ $config['max_awards'] ?? '—' }} premiados; R$ {{ number_format($config['reward_amount'] ?? 0, 2, ',', '.') }} por reconhecimento.</p>
    </details>
    @if ($canManage && $cycle->status === 'draft')
    <details class="apple-card space-y-4 p-6"><summary class="font-bold">Editar nome e período ou cancelar rascunho</summary><form method="POST" action="{{ route('cycles.draft', $cycle) }}" class="space-y-3">@csrf @method('PATCH')<x-workflow-field name="name" label="Nome" :value="$cycle->name" /><x-workflow-field name="starts_at" label="Novo início" type="datetime-local" :value="$cycle->starts_at->format('Y-m-d\TH:i')" /><x-workflow-field name="ends_at" label="Novo fim" type="datetime-local" :value="$cycle->ends_at->format('Y-m-d\TH:i')" /><button class="apple-button">Salvar rascunho</button></form><form method="POST" action="{{ route('cycles.cancel', $cycle) }}" x-data @submit="if (!confirm('Cancelar este rascunho? O histórico será preservado.')) $event.preventDefault()">@csrf<button class="apple-button--light">Cancelar rascunho</button></form><p class="text-sm">Para mudar a metodologia, cancele este rascunho e crie outro ciclo. Regras abertas permanecem imutáveis.</p></details>
    @endif
    @if ($canManage && $cycle->status === 'draft')
    <form class="apple-card space-y-4 p-6" method="POST" action="{{ route('cycles.open', $cycle) }}" x-data="{ selected: @js(array_map('strval', old('ideas', []))) }">
        @csrf
        <h3 class="text-xl font-bold">Último passo: escolha as ideias</h3>
        <p class="text-sm text-slate-600">Selecione as publicações que participarão. Ao confirmar, as regras e os resultados serão fixados para este ciclo.</p>
        <p class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900">A votação começa em {{ $cycle->starts_at->format('d/m/Y H:i') }}. Confirme a abertura antes desse horário.</p>
        @forelse ($ideas as $idea)
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
                <input type="checkbox" name="ideas[]" value="{{ $idea->id }}" x-model="selected" class="mt-1 rounded border-slate-300 text-sky-600">
                <span><span class="block font-semibold">{{ $idea->publication['title'] ?? 'Sem revisão' }}</span><span class="text-sm text-slate-500">{{ $idea->idea_code }}</span></span>
            </label>
        @empty
            <p class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-600">Nenhuma ideia disponível. Conclua a implementação, valide os resultados e publique a ideia para incluí-la aqui.</p>
            <a class="apple-button--light" href="{{ route('ideas.index') }}">Ir para ideias</a>
        @endforelse
        <p class="text-sm text-slate-500" x-text="selected.length + ' ideia(s) selecionada(s)'"></p>
        <button class="apple-button" :disabled="selected.length === 0" @disabled(now()->gte($cycle->starts_at))>Confirmar e programar votação</button>
        @if (now()->gte($cycle->starts_at))<p class="text-sm text-rose-700">O início deste rascunho já passou. Edite o período acima antes de abrir o ciclo.</p>@endif
    </form>
    @endif
    @foreach ($entries as $entry)
        <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">{{ $entry['idea']->idea_code }} — {{ $entry['snapshot']['publication']['title'] }}</h3>
            @foreach (['problem'=>'Problema', 'solution'=>'Solução', 'results'=>'Resultados comprovados e limitações'] as $field=>$label)<div><h4 class="font-semibold">{{ $label }}</h4><p class="whitespace-pre-line">{{ $entry['snapshot']['publication'][$field] }}</p></div>@endforeach
            <p>Setores: {{ implode(', ', $entry['snapshot']['publication']['sectors'] ?? []) }}</p>
            @if ($entry['outcome'])
                <p>Posição: {{ $entry['outcome']['rank'] }} · Resultado real: {{ $entry['outcome']['real_results'] }} · Técnica: {{ $entry['outcome']['technical'] }} · Votação: {{ $entry['outcome']['votes'] ?? 'Sem votos' }} · Votos: {{ $entry['outcome']['vote_count'] }}</p>
                <p class="font-bold">Pontuação final: {{ $entry['outcome']['final_score'] ?? 'Não apurada: votos insuficientes' }}</p>
                @if ($canManage && $cycle->status === 'closed' && $entry['idea']->status === 'voting_closed')<form method="POST" action="{{ route('cycles.finalize', [$cycle, $entry['idea']]) }}" class="space-y-3">@csrf<x-workflow-field name="notes" label="Justificativa do encerramento definitivo" type="textarea" /><button class="apple-button--light">Finalizar processo desta ideia</button><p class="text-sm">Disponível após concluir reconhecimentos pendentes, inclusive para ideias sem prêmio.</p></form>@endif
                @if ($canManage && $entry['outcome']['review_required'])<p class="text-amber-700">Alerta para revisão: há votos com notas idênticas no limite configurado. Verifique o contexto antes de aprovar. Isto não confirma fraude.</p>@if($reviews->has($entry['idea']->id))<p class="text-sm">Revisão registrada: {{ $reviews[$entry['idea']->id]->decision === 'validated' ? 'Apuração válida' : 'Novo ciclo necessário' }} — {{ $reviews[$entry['idea']->id]->notes }}</p>@elseif(auth()->user()->hasSystemPermission('view_audit_logs'))<form method="POST" action="{{ route('cycles.review', [$cycle,$entry['idea']]) }}" class="space-y-3">@csrf<x-workflow-field name="notes" label="Análise do alerta e decisão sobre a validade da apuração" type="textarea" /><label class="block">Decisão<select name="decision" class="block w-full rounded-lg"><option value="validated">Apuração válida após revisão</option><option value="new_cycle_required">Apuração comprometida; realizar novo ciclo</option></select></label><button class="apple-button--light">Registrar revisão auditável</button></form>@endif @endif
            @else
                <a class="text-sky-700 underline" href="{{ route('ideas.vote-form', [$entry['idea'], $cycle]) }}">Conhecer os resultados e votar</a>
            @endif
        </section>
    @endforeach
    @if ($canManage && $cycle->status === 'open')<form method="POST" action="{{ route('cycles.close', $cycle) }}">@csrf<button class="apple-button" @disabled(now()->lt($cycle->ends_at))>Encerrar e apurar após o prazo</button></form>@endif
    @if ($reviewVotes->isNotEmpty())<details class="apple-card space-y-3 p-6"><summary class="font-bold">Revisão excepcional de votos — acesso restrito</summary><p>Invalide somente com justificativa. O registro e suas notas são preservados; o funcionário não poderá enviar outro voto neste ciclo. Após a apuração, utilize um novo ciclo.</p>@foreach ($reviewVotes as $vote)<form method="POST" action="{{ route('votes.invalidate', $vote) }}" class="space-y-3 border-t py-3">@csrf<p>Registro interno #{{ $vote->id }} · Ideia #{{ $vote->idea_id }} · {{ $vote->created_at->format('d/m/Y H:i') }}</p><x-workflow-field name="notes" label="Justificativa obrigatória da invalidação" type="textarea" /><button class="apple-button--light">Invalidar este voto</button></form>@endforeach</details>@endif
    @foreach ($awards as $award)
        <section class="apple-card space-y-4 p-6"><h3 class="text-xl font-bold">Reconhecimento — {{ $award->idea->idea_code }}</h3><p>{{ $award->category }} · {{ $award->score }}/100 · R$ {{ number_format($award->award_amount, 2, ',', '.') }}</p><p>{{ $award->rejected_at ? 'Não aprovado' : ($award->published_at ? 'Divulgado' : ($award->approved_at ? 'Aprovado; divulgação pendente' : 'Proposto; aprovação pendente')) }}</p>
            @if ($canManage && !$award->approved_at && !$award->rejected_at)<form method="POST" action="{{ route('awards.approve', $award) }}" class="space-y-3">@csrf<x-workflow-field name="notes" label="Justificativa da aprovação e da revisão dos alertas" type="textarea" /><button class="apple-button">Aprovar reconhecimento</button></form>@endif
            @if($canManage && !$award->approved_at && !$award->rejected_at)<form method="POST" action="{{ route('awards.reject',$award) }}" class="space-y-3">@csrf<x-workflow-field name="notes" label="Justificativa para não aprovar este reconhecimento" type="textarea" /><button class="apple-button--light">Recusar reconhecimento proposto</button></form>@endif
            @if ($canManage && $award->approved_at && !$award->published_at)<form method="POST" action="{{ route('awards.publish', $award) }}">@csrf<button class="apple-button">Divulgar reconhecimento sem autoria</button></form>@endif
        </section>
    @endforeach
</div>
</x-app-layout>
