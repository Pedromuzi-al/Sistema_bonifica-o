<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $idea->idea_code }} — {{ ($canReview || $isOwner) ? $idea->title : ($idea->publication['title'] ?? 'Publicação em revisão') }}</h2></x-slot>
    <div class="mx-auto max-w-5xl stack-6 px-4 py-8">
        @if($canReview && in_array($idea->status, ['submitted','triage','evaluation','awaiting_information','ready_for_voting','idea_bank']))
            @include('ideas.partials.simple-review')
        @endif
        <section class="apple-card stack-4 p-6">
            <x-idea-status-badge :status="$idea->status" />
            <p>Autor: {{ $idea->visibleAuthorName(auth()->user()) }}</p>
            @if ($canReview || $isOwner)
                <p class="text-sm text-slate-500">Informações privadas da proposta. A publicação revisada aparece abaixo.</p>
                @foreach (['problem_description' => 'Problema identificado', 'solution_description' => 'Solução proposta', 'expected_results' => 'Resultados esperados — estimativas', 'resources_needed' => 'Recursos necessários', 'additional_information' => 'Informações complementares'] as $field => $label)
                    <div><h3 class="font-semibold">{{ $label }}</h3><p class="whitespace-pre-line">{{ $idea->$field }}</p></div>
                @endforeach
            @endif
        </section>
        @if ($canReview || $isOwner || app(\App\Services\PrivateDocumentService::class)->canReview(auth()->user()))
        <section class="apple-card stack-4 p-6">
            <h3 class="text-xl font-bold">Documentos privados</h3>
            <p class="text-sm text-slate-600">Disponíveis somente aos participantes da proposta e à avaliação autorizada. Não aparecem na publicação ou votação. Remova dados pessoais desnecessários antes de enviar.</p>
            @forelse ($documents as $document)
                @php($documentReviewer = app(\App\Services\PrivateDocumentService::class)->canReview(auth()->user()))
                <article class="stack-3 rounded-xl border border-slate-200 p-4">
                    <p class="font-semibold">Documento #{{ $document->id }} · {{ number_format($document->size / 1024, 0, ',', '.') }} KB</p>
                    <p class="text-sm">{{ ['pending'=>'Aguardando revisão de privacidade','approved'=>'Revisado para uso privado','restricted'=>'Restrito à revisão administrativa','deletion_pending'=>'Exclusão em processamento','deleted'=>'Arquivo excluído definitivamente'][$document->review_status] ?? $document->review_status }}</p>
                    @if($document->legal_hold)<p class="text-sm">Preservação obrigatória: exclusão suspensa.</p>@endif
                    @if($document->retention_until)<p class="text-sm">Prazo de retenção aprovado: {{ \Illuminate\Support\Carbon::parse($document->retention_until)->format('d/m/Y') }}.</p>@endif
                    @if(! $document->deleted_at && ! in_array($document->review_status, ['deletion_pending','deleted']) && ($document->review_status !== 'restricted' || $documentReviewer))
                        <a class="text-sky-700 underline" href="{{ route('ideas.documents.download', [$idea, $document->id]) }}">Baixar documento #{{ $document->id }}</a>
                    @endif
                    @if($documentReviewer && ! $document->deleted_at && $document->review_status !== 'deletion_pending')
                        <details><summary class="cursor-pointer font-semibold">Revisar acesso, retenção ou exclusão</summary>
                            @if(now()->timestamp - (int) session('auth.password_confirmed_at',0) < config('auth.password_timeout'))
                            <form method="POST" action="{{ route('ideas.documents.review', [$idea, $document->id]) }}" class="mt-4 stack-4" x-data @submit="if(!confirm('Confirmar a revisão? Se escolher excluir, o arquivo será removido definitivamente.')) $event.preventDefault()">@csrf @method('PATCH')
                                <label class="block">Ação<select name="action" class="block w-full rounded-lg" required><option value="approve">Aprovar para uso privado</option><option value="restrict">Restringir à revisão administrativa</option><option value="delete">Excluir arquivo definitivamente</option></select></label>
                                <x-workflow-field name="notes" label="Justificativa e tratamento dos dados pessoais (mínimo de 20 caracteres)" type="textarea" minlength="20" maxlength="5000" />
                                <x-workflow-field name="retention_until" label="Excluir após o prazo aprovado (opcional)" type="date" :required="false" :value="$document->retention_until ? substr($document->retention_until,0,10) : ''" />
                                <input type="hidden" name="legal_hold" value="0"><label class="block"><input type="checkbox" name="legal_hold" value="1" @checked($document->legal_hold)> Preservação obrigatória: suspender exclusão</label>
                                <label class="block"><input type="checkbox" name="review_confirmed" value="1" required> Revisei os dados pessoais e tenho autorização para a decisão e o prazo informados.</label>
                                <button class="apple-button--light">Salvar revisão do documento #{{ $document->id }}</button>
                            </form>
                            @else
                                <a class="apple-button--light mt-4" href="{{ route('password.confirm', ['return_to'=>'idea', 'idea_id'=>$idea->id]) }}">Confirmar senha para revisar documento</a>
                            @endif
                        </details>
                    @endif
                </article>
            @empty<p class="text-sm">Nenhum documento enviado.</p>@endforelse
            @if (config('innovation.attachments_enabled') && in_array($idea->status, ['submitted','triage','evaluation','awaiting_information','implementation','validation']))
            <form method="POST" enctype="multipart/form-data" action="{{ route('ideas.documents.store', $idea) }}" class="stack-3">@csrf<label class="block">Adicionar documento (PDF, PNG, JPG ou TXT; até 10 MB)<input class="mt-2 block w-full" type="file" name="document" accept=".pdf,.png,.jpg,.jpeg,.txt" required></label><button class="apple-button--light">Enviar documento privado</button></form>
            @endif
        </section>
        @endif
        @if ($contributorOptions->isNotEmpty() && in_array($idea->status, ['submitted','triage']) && !$idea->votingCycles()->whereNotNull('frozen_configuration')->exists())
        <form class="apple-card stack-4 p-6" method="POST" action="{{ route('ideas.contributors', $idea) }}" x-data="{ rows: @js(old('contributors', $contributors ?: [['user_id'=>$idea->author_id, 'share'=>100]])) }">@csrf @method('PUT')
            <h3 class="text-xl font-bold">Participantes e divisão do reconhecimento — privado</h3><p class="text-sm">Confirme com os participantes antes de salvar. Inclua o autor principal e distribua 100%. A divisão fica fixa após a triagem e impede votos e avaliações dos participantes.</p>
            <template x-for="(row,index) in rows" :key="index"><div class="flex flex-wrap items-end gap-3"><label>Participante<select x-model="row.user_id" :name="`contributors[${index}][user_id]`" class="block rounded-lg" required>@foreach ($contributorOptions as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></label><label>Participação (%)<input type="number" min="1" max="100" x-model="row.share" :name="`contributors[${index}][share]`" class="block w-28 rounded-lg" required></label><button type="button" class="text-rose-700" @click="rows.splice(index,1)" x-show="rows.length > 1">Remover</button></div></template>
            <button type="button" class="apple-button--light" @click="rows.push({user_id:'',share:1})" :disabled="rows.length >= 20">Adicionar participante</button><button class="apple-button">Salvar divisão</button>
        </form>
        @endif
        @if ($idea->publication)
            <section class="apple-card stack-4 p-6">
                <h3 class="text-xl font-bold">{{ ($idea->publication['kind'] ?? 'results') === 'results' && $idea->result?->validated_at ? 'Publicação revisada — Ideia implementada' : 'Publicação revisada — Proposta de melhoria' }}</h3>
                <x-implementation-status :status="\App\Support\ImplementationStatus::forIdea($idea)" />
                @foreach (['title' => 'Título', 'problem' => 'Problema', 'solution' => 'Solução proposta', 'results' => (($idea->publication['kind'] ?? 'results') === 'results' && $idea->result?->validated_at ? 'Resultados comprovados e limitações' : 'Benefícios esperados'), 'category' => 'Categoria'] as $field => $label)
                    <div><h4 class="font-semibold">{{ $label }}</h4><p class="whitespace-pre-line">{{ $idea->publication[$field] ?? '' }}</p></div>
                @endforeach
                <p>Setores beneficiados: {{ implode(', ', $idea->publication['sectors'] ?? []) }}</p>
                @if(!empty($idea->publication['implementation_completed_at']))<p>Conclusão da implementação: {{ !empty($idea->publication['implementation_completed_at']) ? \Illuminate\Support\Carbon::parse($idea->publication['implementation_completed_at'])->format('d/m/Y') : 'Não informada' }}</p>@endif
            </section>
        @endif
        @foreach ($cycles as $cycle)
            <section class="apple-card stack-4 p-6">
                <h3 class="text-xl font-bold">Esta ideia participa de uma votação</h3>
                <p>{{ $cycle->name }} · Até {{ $cycle->local_ends_at->format('d/m/Y H:i') }}</p>
                <p>{{ $voteStates[$cycle->id]['message'] }}</p>
                <a class="{{ $voteStates[$cycle->id]['canVote'] ? 'apple-button' : 'apple-button--light' }}" href="{{ route('ideas.vote-form', [$idea, $cycle]) }}">{{ $voteStates[$cycle->id]['canVote'] ? 'Conhecer a ideia e votar' : 'Acompanhar esta votação' }}</a>
            </section>
        @endforeach
        @if ($canReview || $isOwner)
            @if ($idea->implementationPlan)
                <section class="apple-card stack-2 p-6"><h3 class="text-xl font-bold">Plano de implementação</h3><p>{{ $idea->implementationPlan->title }}</p><p class="whitespace-pre-line">{{ $idea->implementationPlan->plan }}</p><p>Prazo: {{ $idea->implementationPlan->due_date?->format('d/m/Y') }}</p></section>
            @endif
            @if ($idea->result)
                <section class="apple-card stack-3 p-6"><h3 class="text-xl font-bold">Resultados medidos</h3><p>{{ $idea->result->validated_at ? 'Validados em '.$idea->result->validated_at->format('d/m/Y H:i') : 'Aguardando validação' }}</p><p>{{ $idea->result->impact_summary }}</p><p>Limitações: {{ $idea->result->limitations ?: 'Não registradas' }}</p>
                    @foreach ($idea->result->measurements ?? [] as $measurement)
                        <div class="rounded-lg border p-3"><h4 class="font-semibold">{{ $measurement['indicator'] }} ({{ $measurement['unit'] }})</h4><p>Antes: {{ $measurement['before'] ?? 'Sem dados' }} · Depois: {{ $measurement['after'] ?? 'Sem dados' }}</p><p>Diferença: {{ $measurement['difference'] ?? 'Não calculável' }} · Percentual: {{ isset($measurement['percentage']) ? $measurement['percentage'].'%' : 'Não calculável' }}</p><p>Período: {{ \Illuminate\Support\Carbon::parse($measurement['period_start'])->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($measurement['period_end'])->format('d/m/Y') }}</p><p>Evidência: {{ $measurement['evidence'] }}</p><p>{{ $measurement['qualitative'] ?? '' }}</p></div>
                    @endforeach
                </section>
            @endif
            <section class="apple-card stack-3 p-6"><h3 class="text-xl font-bold">O que aconteceu com sua ideia</h3>
                @foreach ($history as $event)<p><strong>{{ $event->created_at->format('d/m/Y H:i') }}</strong> — {{ \App\Models\Idea::LABELS[$event->to_status] ?? $event->to_status }}: {{ $event->notes }}</p>@endforeach
            </section>
        @endif
        @if ($isOwner && $idea->status === 'awaiting_information')
            <form class="apple-card stack-4 p-6" method="POST" action="{{ route('ideas.reply', $idea) }}">@csrf<h3 class="text-xl font-bold">Responder pedido de ajuste</h3><x-workflow-field name="notes" label="Explique o ajuste ou complemente sua proposta" type="textarea" /><button class="apple-button">Enviar ajuste para revisão</button></form>
        @endif
        @if ($canReview)
            @if(in_array($idea->status, ['approved','implementation']))
                <details class="apple-card p-6"><summary class="cursor-pointer font-semibold">Atualizar publicação da proposta</summary>
                    <p>A proposta já avançou. A publicação pode ser revisada sem alterar a execução.</p>
                    <form method="POST" action="{{ route('ideas.publish-proposal', $idea) }}" class="mt-4 stack-4">@csrf
                        @foreach(['title'=>'Título público','problem'=>'Problema','solution'=>'Solução proposta','results'=>'Benefícios esperados','category'=>'Categoria'] as $field=>$label)
                            <x-workflow-field :name="$field" :label="$label" :type="in_array($field,['title','category']) ? 'text' : 'textarea'" :value="$idea->publication[$field] ?? ''" />
                        @endforeach
                        <label class="block"><input type="checkbox" name="privacy_reviewed" value="1" required> Conferi o texto e removi informações pessoais e confidenciais.</label>
                        <button class="apple-button">Atualizar publicação</button>
                    </form>
                </details>
            @endif
            <details class="apple-card p-6"><summary class="cursor-pointer font-semibold">Análise técnica e execução (opcional antes da seleção)</summary>
            <p class="my-3 text-sm">Para votar, use a revisão simples acima. Estes controles atendem análises técnicas e o acompanhamento posterior.</p>
            @if(array_diff(\App\Services\IdeaWorkflowService::TRANSITIONS[$idea->status] ?? [], ['voting','voting_closed','finalized']))
            <section class="apple-card stack-4 p-6"><h3 class="text-xl font-bold">Andamento</h3>
                <form method="POST" action="{{ route('ideas.approve', $idea) }}" class="stack-3">@csrf
                    <label class="block">Próxima etapa<select name="status" class="block w-full rounded-lg border-slate-300" required>
                        @foreach (\App\Services\IdeaWorkflowService::TRANSITIONS[$idea->status] ?? [] as $status)
                            @if (!in_array($status, ['voting', 'voting_closed', 'finalized']))<option value="{{ $status }}">{{ \App\Models\Idea::LABELS[$status] }}</option>@endif
                        @endforeach
                    </select></label>
                    <x-workflow-field name="notes" label="Justificativa / solicitação de informações" type="textarea" /><button class="apple-button">Atualizar etapa</button>
                </form>
            </section>
            @endif
            @if ($idea->canRecordTechnicalEvaluation())
                <form class="apple-card stack-4 p-6" method="POST" action="{{ route('ideas.evaluate', $idea) }}">@csrf<h3 class="text-xl font-bold">Avaliação técnica</h3>
                    @forelse ($criteria as $criterion)<x-workflow-field name="scores[{{ $criterion->id }}]" :label="$criterion->name.' — '.$criterion->description" type="number" min="1" max="5" step="1" />@empty<div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900"><p class="font-semibold">Cadastre os critérios técnicos antes de avaliar.</p><p class="mt-1">Ainda não há campos de notas. Solicite ao administrador o cadastro em Administração → Critérios técnicos → Novo critério técnico.</p>@can('manage-admin')<a href="{{ route('admin.criteria.index') }}" class="mt-2 inline-block underline">Configurar critérios técnicos</a>@endcan</div>@endforelse
                    <label>Recomendação<select name="recommendation" class="block w-full rounded-lg"><option value="approve">Aprovar</option><option value="reject">Rejeitar</option><option value="clarify">Solicitar esclarecimentos</option></select></label>
                    <x-workflow-field name="notes" label="Parecer e justificativa" type="textarea" /><button class="apple-button" @disabled($criteria->isEmpty())>Registrar parecer</button>
                </form>
            @endif
            @foreach ($evaluations as $evaluation)<section class="apple-card p-6"><h3 class="font-bold">Parecer técnico — {{ number_format($evaluation->technical_score, 2, ',', '.') }}/100</h3><p>{{ $evaluation->notes }}</p><p>{{ $evaluation->recommendation }}</p>@foreach ($evaluation->scores as $score)<p>{{ $score->notes }}: {{ $score->score }}/5</p>@endforeach</section>@endforeach
            </details>
            @if (in_array($idea->status, ['approved', 'implementation']))
                <form class="apple-card stack-4 p-6" method="POST" action="{{ route('ideas.plan', $idea) }}">@csrf<h3 class="text-xl font-bold">Plano de implementação</h3><x-workflow-field name="title" label="Etapas da execução" :value="$idea->implementationPlan?->title ?? ''" /><x-workflow-field name="plan" label="Plano e etapas" type="textarea" :value="$idea->implementationPlan?->plan ?? ''" />
                    <label>Responsável<select name="owner_user_id" class="block w-full rounded-lg" required>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></label>
                    <x-workflow-field name="due_date" label="Prazo" type="date" :value="$idea->implementationPlan?->due_date?->format('Y-m-d') ?? ''" /><button class="apple-button">Salvar plano</button>
                </form>
            @endif
            @if (in_array($idea->status, ['implementation', 'validation']))
                <form x-data="{ count: {{ max(1, count($idea->result?->measurements ?? [])) }} }" class="apple-card stack-4 p-6" method="POST" action="{{ route('ideas.results', $idea) }}">@csrf<h3 class="text-xl font-bold">Registrar resultados reais</h3><x-workflow-field name="impact_summary" label="Resumo dos resultados" type="textarea" :value="$idea->result?->impact_summary ?? ''" /><x-workflow-field name="limitations" label="Limitações dos dados" type="textarea" :required="false" :value="$idea->result?->limitations ?? ''" />
                    @for ($i=0; $i<20; $i++)
                        <fieldset @disabled($i >= max(1, count($idea->result?->measurements ?? []))) x-show="count > {{ $i }}" :disabled="count <= {{ $i }}" class="stack-3 rounded-lg border p-4"><legend>Indicador {{ $i+1 }}</legend>
                            @foreach (['indicator' => 'Nome do indicador', 'unit' => 'Unidade de medida', 'before' => 'Situação anterior', 'after' => 'Situação posterior', 'period_start' => 'Início da observação', 'period_end' => 'Fim da observação', 'evidence' => 'Fonte / evidência', 'qualitative' => 'Descrição qualitativa / escala utilizada'] as $field => $label)
                                <x-workflow-field name="measurements[{{ $i }}][{{ $field }}]" :label="$label" :type="in_array($field, ['before', 'after']) ? 'number' : (str_starts_with($field, 'period_') ? 'date' : 'text')" :required="!in_array($field, ['before','after','qualitative'])" :value="$idea->result?->measurements[$i][$field] ?? ''" step="any" />
                            @endforeach
                        </fieldset>
                    @endfor
                    <button type="button" @click="count = Math.min(count + 1, 20)" class="apple-button--light">Adicionar indicador</button><button type="button" @click="count = Math.max(count - 1, 1)" class="apple-button--light">Remover último</button><button class="apple-button">Salvar resultados</button>
                </form>
            @endif
            @if ($idea->status === 'validation')<form class="apple-card stack-4 p-6" method="POST" action="{{ route('ideas.validate-results', $idea) }}">@csrf<h3 class="text-xl font-bold">Validar evidências e resultados</h3><x-workflow-field name="notes" label="Justificativa da validação" type="textarea" /><button class="apple-button">Confirmar validação</button></form>@endif
            @if ($idea->status === 'completed')
                <form class="apple-card stack-4 p-6" method="POST" action="{{ route('ideas.publish', $idea) }}">@csrf<h3 class="text-xl font-bold">Revisar publicação</h3><p>Revise nomes, referências pessoais e informações confidenciais. Os funcionários verão somente esta versão.</p>
                    @foreach (['title' => 'Título público', 'problem' => 'Problema', 'solution' => 'Solução aplicada', 'results' => 'Resultados comprovados, indicadores, período e limitações', 'category' => 'Categoria'] as $field => $label)<x-workflow-field :name="$field" :label="$label" :type="in_array($field,['title','category']) ? 'text' : 'textarea'" :value="$idea->publication[$field] ?? ''" />@endforeach
                    <label class="block"><input name="privacy_reviewed" value="1" type="checkbox" required> Conferi as evidências e removi dados pessoais e informações confidenciais.</label><button class="apple-button">Publicar versão revisada</button>
                </form>
            @endif
        @endif
        @if (!$isOwner && !$idea->hasConflict(auth()->user()))<details class="apple-card p-6"><summary class="cursor-pointer font-semibold">Você tem algum conflito de interesse nesta ideia?</summary><form class="mt-4 stack-3" method="POST" action="{{ route('ideas.conflict', $idea) }}">@csrf<h3 class="font-bold">Declaração de conflito de interesse</h3><x-workflow-field name="reason" label="Motivo privado do impedimento" type="textarea" /><button class="apple-button--light">Declarar impedimento</button></form></details>@endif
    </div>
</x-app-layout>
