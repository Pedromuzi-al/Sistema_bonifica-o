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
        <p>Desempate: resultados reais, avaliação técnica, votação e código da ideia.</p>
        <p>Categoria: {{ $config['category'] ?? 'Não configurada' }}; mínimo {{ $config['minimum_score'] ?? '—' }}/100; até {{ $config['max_awards'] ?? '—' }} premiados; R$ {{ number_format($config['reward_amount'] ?? 0, 2, ',', '.') }} por reconhecimento.</p>
    </details>
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
        @if (now()->gte($cycle->starts_at))<p class="text-sm text-rose-700">O início deste rascunho já passou. Crie um novo ciclo com uma data futura.</p>@endif
    </form>
    @endif
    @foreach ($entries as $entry)
        <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">{{ $entry['idea']->idea_code }} — {{ $entry['snapshot']['publication']['title'] }}</h3>
            @foreach (['problem'=>'Problema', 'solution'=>'Solução', 'results'=>'Resultados comprovados e limitações'] as $field=>$label)<div><h4 class="font-semibold">{{ $label }}</h4><p class="whitespace-pre-line">{{ $entry['snapshot']['publication'][$field] }}</p></div>@endforeach
            <p>Setores: {{ implode(', ', $entry['snapshot']['publication']['sectors'] ?? []) }}</p>
            @if ($entry['outcome'])
                <p>Posição: {{ $entry['outcome']['rank'] }} · Resultado real: {{ $entry['outcome']['real_results'] }} · Técnica: {{ $entry['outcome']['technical'] }} · Votação: {{ $entry['outcome']['votes'] ?? 'Sem votos' }} · Votos: {{ $entry['outcome']['vote_count'] }}</p>
                <p class="font-bold">Pontuação final: {{ $entry['outcome']['final_score'] ?? 'Não apurada: votos insuficientes' }}</p>
                @if ($canManage && $entry['outcome']['review_required'])<p class="text-amber-700">Alerta para revisão: há pelo menos cinco votos com notas idênticas. Verifique o contexto antes de aprovar. Isto não confirma fraude.</p>@endif
            @else
                <a class="text-sky-700 underline" href="{{ route('ideas.show', $entry['idea']) }}">Abrir ideia e votar</a>
            @endif
        </section>
    @endforeach
    @if ($canManage && $cycle->status === 'open')<form method="POST" action="{{ route('cycles.close', $cycle) }}">@csrf<button class="apple-button" @disabled(now()->lt($cycle->ends_at))>Encerrar e apurar após o prazo</button></form>@endif
    @if ($reviewVotes->isNotEmpty())<details class="apple-card space-y-3 p-6"><summary class="font-bold">Revisão excepcional de votos — acesso restrito</summary><p>Invalide somente com justificativa. O registro e suas notas são preservados; o funcionário não poderá enviar outro voto neste ciclo. Após a apuração, utilize um novo ciclo.</p>@foreach ($reviewVotes as $vote)<form method="POST" action="{{ route('votes.invalidate', $vote) }}" class="space-y-3 border-t py-3">@csrf<p>Registro interno #{{ $vote->id }} · Ideia #{{ $vote->idea_id }} · {{ $vote->created_at->format('d/m/Y H:i') }}</p><x-workflow-field name="notes" label="Justificativa obrigatória da invalidação" type="textarea" /><button class="apple-button--light">Invalidar este voto</button></form>@endforeach</details>@endif
    @foreach ($awards as $award)
        <section class="apple-card space-y-4 p-6"><h3 class="text-xl font-bold">Reconhecimento — {{ $award->idea->idea_code }}</h3><p>{{ $award->category }} · {{ $award->score }}/100 · R$ {{ number_format($award->award_amount, 2, ',', '.') }}</p><p>{{ $award->published_at ? 'Divulgado' : ($award->approved_at ? 'Aprovado; divulgação pendente' : 'Proposto; aprovação pendente') }}</p>
            @if ($canManage && !$award->approved_at)<form method="POST" action="{{ route('awards.approve', $award) }}" class="space-y-3">@csrf<x-workflow-field name="notes" label="Justificativa da aprovação e da revisão dos alertas" type="textarea" /><button class="apple-button">Aprovar reconhecimento</button></form>@endif
            @if ($canManage && $award->approved_at && !$award->published_at)<form method="POST" action="{{ route('awards.publish', $award) }}">@csrf<button class="apple-button">Divulgar reconhecimento sem autoria</button></form>@endif
        </section>
    @endforeach
</div>
</x-app-layout>
