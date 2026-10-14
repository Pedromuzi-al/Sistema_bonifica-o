<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $idea->idea_code }} — {{ ($canReview || $isOwner) ? $idea->title : ($idea->publication['title'] ?? 'Publicação em revisão') }}</h2></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
        <section class="apple-card space-y-4 p-6">
            <x-idea-status-badge :status="$idea->status" />
            <p>Autor: {{ $idea->visibleAuthorName(auth()->user()) }}</p>
            @if ($canReview || $isOwner)
                <p class="text-sm text-slate-500">Informações privadas da proposta. A publicação revisada aparece abaixo.</p>
                @foreach (['problem_description' => 'Problema identificado', 'solution_description' => 'Solução proposta', 'expected_results' => 'Resultados esperados — estimativas', 'resources_needed' => 'Recursos necessários', 'additional_information' => 'Informações complementares'] as $field => $label)
                    <div><h3 class="font-semibold">{{ $label }}</h3><p class="whitespace-pre-line">{{ $idea->$field }}</p></div>
                @endforeach
            @endif
        </section>
        @if ($idea->publication)
            <section class="apple-card space-y-4 p-6">
                <h3 class="text-xl font-bold">Publicação revisada — Ideia implementada</h3>
                @foreach (['title' => 'Título', 'problem' => 'Problema', 'solution' => 'Solução aplicada', 'results' => 'Resultados comprovados e limitações', 'category' => 'Categoria'] as $field => $label)
                    <div><h4 class="font-semibold">{{ $label }}</h4><p class="whitespace-pre-line">{{ $idea->publication[$field] ?? '' }}</p></div>
                @endforeach
                <p>Setores beneficiados: {{ implode(', ', $idea->publication['sectors'] ?? []) }}</p>
                <p>Conclusão da implementação: {{ !empty($idea->publication['implementation_completed_at']) ? \Illuminate\Support\Carbon::parse($idea->publication['implementation_completed_at'])->format('d/m/Y') : 'Não informada' }}</p>
            </section>
        @endif
        @foreach ($cycles as $cycle)
            <section class="apple-card space-y-4 p-6">
                <h3 class="text-xl font-bold">{{ $cycle->name }}</h3>
                <p>Período: {{ $cycle->starts_at->format('d/m/Y H:i') }} até {{ $cycle->ends_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</p>
                <a class="text-sky-700 underline" href="{{ route('cycles.show', $cycle) }}">Consultar metodologia e publicação congelada</a>
                @if (auth()->user()->can('vote', $idea) && now()->gte($cycle->starts_at) && now()->lt($cycle->ends_at) && !$idea->votes()->where('user_id', auth()->id())->where('voting_cycle_id', $cycle->id)->exists())
                    <form method="POST" action="{{ route('ideas.vote', $idea) }}" class="space-y-4">@csrf
                        <input type="hidden" name="voting_cycle_id" value="{{ $cycle->id }}">
                        @foreach ($cycle->frozen_configuration['criteria'] ?? [] as $criterion)
                            <label class="block"><span class="font-semibold">{{ $criterion['name'] }} ({{ $criterion['weight'] }}%)</span><span class="block text-sm">{{ $criterion['description'] }}</span>
                                <select name="scores[{{ $criterion['id'] }}]" class="w-full rounded-lg border-slate-300" @required(!($cycle->frozen_configuration['allow_abstention'] ?? false))>
                                    <option value="">{{ ($cycle->frozen_configuration['allow_abstention'] ?? false) ? 'Abster-se por falta de informação' : 'Selecione uma nota' }}</option>
                                    @for ($score=1; $score<=5; $score++)<option value="{{ $score }}">{{ $score }}</option>@endfor
                                </select>
                            </label>
                        @endforeach
                        <p class="text-sm">Notas de 1 (menor impacto) a 5 (maior impacto). O voto registrado não pode ser editado.</p>
                        <button class="apple-button">Registrar voto</button>
                    </form>
                @else
                    <p class="text-sm">Votação indisponível para você, fora do período ou já registrada.</p>
                @endif
            </section>
        @endforeach
        @if ($canReview || $isOwner)
            @if ($idea->implementationPlan)
                <section class="apple-card space-y-2 p-6"><h3 class="text-xl font-bold">Plano de implementação</h3><p>{{ $idea->implementationPlan->title }}</p><p class="whitespace-pre-line">{{ $idea->implementationPlan->plan }}</p><p>Prazo: {{ $idea->implementationPlan->due_date?->format('d/m/Y') }}</p></section>
            @endif
            @if ($idea->result)
                <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">Resultados medidos</h3><p>{{ $idea->result->validated_at ? 'Validados em '.$idea->result->validated_at->format('d/m/Y H:i') : 'Aguardando validação' }}</p><p>{{ $idea->result->impact_summary }}</p><p>Limitações: {{ $idea->result->limitations ?: 'Não registradas' }}</p>
                    @foreach ($idea->result->measurements ?? [] as $measurement)
                        <div class="rounded-lg border p-3"><h4 class="font-semibold">{{ $measurement['indicator'] }} ({{ $measurement['unit'] }})</h4><p>Antes: {{ $measurement['before'] ?? 'Sem dados' }} · Depois: {{ $measurement['after'] ?? 'Sem dados' }}</p><p>Diferença: {{ $measurement['difference'] ?? 'Não calculável' }} · Percentual: {{ isset($measurement['percentage']) ? $measurement['percentage'].'%' : 'Não calculável' }}</p><p>Período: {{ \Illuminate\Support\Carbon::parse($measurement['period_start'])->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($measurement['period_end'])->format('d/m/Y') }}</p><p>Evidência: {{ $measurement['evidence'] }}</p><p>{{ $measurement['qualitative'] ?? '' }}</p></div>
                    @endforeach
                </section>
            @endif
            <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">Histórico privado</h3>
                @foreach ($history as $event)<p><strong>{{ $event->created_at->format('d/m/Y H:i') }}</strong> — {{ \App\Models\Idea::LABELS[$event->to_status] ?? $event->to_status }}: {{ $event->notes }}</p>@endforeach
            </section>
        @endif
        @if ($isOwner && $idea->status === 'awaiting_information')
            <form class="apple-card space-y-4 p-6" method="POST" action="{{ route('ideas.reply', $idea) }}">@csrf<h3 class="text-xl font-bold">Responder solicitação de informações</h3><x-workflow-field name="notes" label="Esclarecimento privado" type="textarea" /><button class="apple-button">Enviar resposta</button></form>
        @endif
        @if ($canReview)
            <section class="apple-card space-y-4 p-6"><h3 class="text-xl font-bold">Andamento</h3>
                <form method="POST" action="{{ route('ideas.approve', $idea) }}" class="space-y-3">@csrf
                    <label class="block">Próxima etapa<select name="status" class="block w-full rounded-lg border-slate-300" required>
                        @foreach (\App\Services\IdeaWorkflowService::TRANSITIONS[$idea->status] ?? [] as $status)
                            @if (!in_array($status, ['voting', 'voting_closed', 'finalized']))<option value="{{ $status }}">{{ \App\Models\Idea::LABELS[$status] }}</option>@endif
                        @endforeach
                    </select></label>
                    <x-workflow-field name="notes" label="Justificativa / solicitação de informações" type="textarea" /><button class="apple-button">Atualizar etapa</button>
                </form>
            </section>
            @if ($idea->status === 'evaluation')
                <form class="apple-card space-y-4 p-6" method="POST" action="{{ route('ideas.evaluate', $idea) }}">@csrf<h3 class="text-xl font-bold">Avaliação técnica</h3>
                    @forelse ($criteria as $criterion)<x-workflow-field name="scores[{{ $criterion->id }}]" :label="$criterion->name.' — '.$criterion->description" type="number" min="1" max="5" step="1" />@empty<p>O administrador deve cadastrar critérios técnicos.</p>@endforelse
                    <label>Recomendação<select name="recommendation" class="block w-full rounded-lg"><option value="approve">Aprovar</option><option value="reject">Rejeitar</option><option value="clarify">Solicitar esclarecimentos</option></select></label>
                    <x-workflow-field name="notes" label="Parecer e justificativa" type="textarea" /><button class="apple-button">Registrar parecer</button>
                </form>
            @endif
            @foreach ($evaluations as $evaluation)<section class="apple-card p-6"><h3 class="font-bold">Parecer técnico — {{ number_format($evaluation->technical_score, 2, ',', '.') }}/100</h3><p>{{ $evaluation->notes }}</p><p>{{ $evaluation->recommendation }}</p>@foreach ($evaluation->scores as $score)<p>{{ $score->notes }}: {{ $score->score }}/5</p>@endforeach</section>@endforeach
            @if (in_array($idea->status, ['approved', 'implementation']))
                <form class="apple-card space-y-4 p-6" method="POST" action="{{ route('ideas.plan', $idea) }}">@csrf<h3 class="text-xl font-bold">Plano de implementação</h3><x-workflow-field name="title" label="Etapas da execução" :value="$idea->implementationPlan?->title ?? ''" /><x-workflow-field name="plan" label="Plano e etapas" type="textarea" :value="$idea->implementationPlan?->plan ?? ''" />
                    <label>Responsável<select name="owner_user_id" class="block w-full rounded-lg" required>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></label>
                    <x-workflow-field name="due_date" label="Prazo" type="date" :value="$idea->implementationPlan?->due_date?->format('Y-m-d') ?? ''" /><button class="apple-button">Salvar plano</button>
                </form>
            @endif
            @if (in_array($idea->status, ['implementation', 'validation']))
                <form x-data="{ count: {{ max(1, count($idea->result?->measurements ?? [])) }} }" class="apple-card space-y-4 p-6" method="POST" action="{{ route('ideas.results', $idea) }}">@csrf<h3 class="text-xl font-bold">Registrar resultados reais</h3><x-workflow-field name="impact_summary" label="Resumo dos resultados" type="textarea" :value="$idea->result?->impact_summary ?? ''" /><x-workflow-field name="limitations" label="Limitações dos dados" type="textarea" :required="false" :value="$idea->result?->limitations ?? ''" />
                    @for ($i=0; $i<20; $i++)
                        <fieldset @disabled($i >= max(1, count($idea->result?->measurements ?? []))) x-show="count > {{ $i }}" :disabled="count <= {{ $i }}" class="space-y-3 rounded-lg border p-4"><legend>Indicador {{ $i+1 }}</legend>
                            @foreach (['indicator' => 'Nome do indicador', 'unit' => 'Unidade de medida', 'before' => 'Situação anterior', 'after' => 'Situação posterior', 'period_start' => 'Início da observação', 'period_end' => 'Fim da observação', 'evidence' => 'Fonte / evidência', 'qualitative' => 'Descrição qualitativa / escala utilizada'] as $field => $label)
                                <x-workflow-field name="measurements[{{ $i }}][{{ $field }}]" :label="$label" :type="in_array($field, ['before', 'after']) ? 'number' : (str_starts_with($field, 'period_') ? 'date' : 'text')" :required="!in_array($field, ['before','after','qualitative'])" :value="$idea->result?->measurements[$i][$field] ?? ''" step="any" />
                            @endforeach
                        </fieldset>
                    @endfor
                    <button type="button" @click="count = Math.min(count + 1, 20)" class="apple-button--light">Adicionar indicador</button><button type="button" @click="count = Math.max(count - 1, 1)" class="apple-button--light">Remover último</button><button class="apple-button">Salvar resultados</button>
                </form>
            @endif
            @if ($idea->status === 'validation')<form class="apple-card space-y-4 p-6" method="POST" action="{{ route('ideas.validate-results', $idea) }}">@csrf<h3 class="text-xl font-bold">Validar evidências e resultados</h3><x-workflow-field name="notes" label="Justificativa da validação" type="textarea" /><button class="apple-button">Confirmar validação</button></form>@endif
            @if ($idea->status === 'completed')
                <form class="apple-card space-y-4 p-6" method="POST" action="{{ route('ideas.publish', $idea) }}">@csrf<h3 class="text-xl font-bold">Revisar publicação</h3><p>Revise nomes, referências pessoais e informações confidenciais. Os funcionários verão somente esta versão.</p>
                    @foreach (['title' => 'Título público', 'problem' => 'Problema', 'solution' => 'Solução aplicada', 'results' => 'Resultados comprovados, indicadores, período e limitações', 'category' => 'Categoria'] as $field => $label)<x-workflow-field :name="$field" :label="$label" :type="in_array($field,['title','category']) ? 'text' : 'textarea'" :value="$idea->publication[$field] ?? ''" />@endforeach
                    <label class="block"><input name="privacy_reviewed" value="1" type="checkbox" required> Conferi as evidências e removi dados pessoais e informações confidenciais.</label><button class="apple-button">Publicar versão revisada</button>
                </form>
            @endif
        @endif
        @if (!$isOwner && !$idea->hasConflict(auth()->user()))<form class="apple-card space-y-3 p-6" method="POST" action="{{ route('ideas.conflict', $idea) }}">@csrf<h3 class="font-bold">Declaração de conflito de interesse</h3><x-workflow-field name="reason" label="Motivo privado do impedimento" type="textarea" /><button class="apple-button--light">Declarar impedimento</button></form>@endif
    </div>
</x-app-layout>
