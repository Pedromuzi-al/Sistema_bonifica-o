<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Ajude a reconhecer uma boa ideia</h2><p class="mt-2 text-slate-600">Conheça a ideia, confira a situação da implementação e avalie seus benefícios.</p></x-slot>
    @php
        $publication = $snapshot['publication'];
        $criteria = $cycle->frozen_configuration['criteria'];
        $answers = collect($criteria)->mapWithKeys(fn ($criterion) => [(string) $criterion['id'] => (string) old('scores.'.$criterion['id'], '')]);
        $labels = [1 => 'Muito baixo', 2 => 'Baixo', 3 => 'Moderado', 4 => 'Alto', 5 => 'Muito alto'];
    @endphp
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-8">
        <a href="{{ route('cycles.show', $cycle) }}" class="inline-block text-sm text-sky-700 underline">Voltar para as ideias desta votação</a>
        <section class="apple-card space-y-5 p-6">
            <p class="text-sm text-slate-500">{{ $idea->idea_code }} · {{ $cycle->name }}</p>
            <h3 class="text-2xl font-bold">{{ $publication['title'] }}</h3>
            <x-implementation-status :snapshot="$snapshot" />
            @if($cycle->isSelection())<p class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900">{{ \App\Support\ImplementationStatus::fromSnapshot($snapshot) === 'planned' ? 'Esta ideia ainda não foi implementada. Avalie o benefício esperado.' : 'Avalie o potencial da proposta considerando a situação da implementação apresentada acima.' }}</p>@endif
            @foreach (['problem' => 'Problema identificado', 'solution' => 'Solução proposta', 'results' => 'Benefícios apresentados'] as $field => $label)<div><h4 class="font-semibold">{{ $label }}</h4><p class="whitespace-pre-line text-slate-600">{{ $publication[$field] }}</p></div>@endforeach
            <p class="text-sm">Setores beneficiados: {{ implode(', ', $publication['sectors'] ?? []) }}</p>
            <p class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900">A autoria fica protegida. Avalie os benefícios apresentados, e não quem você imagina que enviou a ideia.</p>
        </section>
        @if ($voteState['canVote'])
            <form method="POST" action="{{ route('ideas.vote', $idea) }}" class="apple-card space-y-6 p-5 sm:p-8"
                x-data="{ answers: @js($answers), reviewing: false, emptyError: false, sending: false, labels: @js($labels) }"
                @submit="if (sending) { $event.preventDefault(); } else if (!Object.values(answers).some(value => value !== '')) { $event.preventDefault(); emptyError = true; } else if (!reviewing) { $event.preventDefault(); reviewing = true; $nextTick(() => $refs.summary.focus()); } else { sending = true; }">
                @csrf
                <input type="hidden" name="voting_cycle_id" value="{{ $cycle->id }}">
                <div x-show="!reviewing" class="space-y-6">
                    <div><h3 class="text-xl font-bold">Como você avalia esta ideia?</h3><p class="mt-2 text-sm text-slate-600">Escolha uma resposta em cada pergunta. Nenhuma nota vem marcada.</p><p class="mt-1 text-sm text-slate-500">Você pode votar até {{ $cycle->local_ends_at->format('d/m/Y H:i') }}.</p></div>
                    @foreach ($criteria as $criterion)
                        <fieldset class="space-y-3 border-t border-slate-200 pt-4">
                            <legend class="font-bold">{{ $loop->iteration }}. {{ $criterion['name'] }}</legend>
                            <p id="criterion-help-{{ $criterion['id'] }}" class="text-sm text-slate-600">{{ $criterion['description'] }}</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-5">
                                @foreach ($labels as $score => $label)
                                    <label class="vote-choice flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 p-3 sm:flex-col sm:text-center">
                                        <input type="radio" name="scores[{{ $criterion['id'] }}]" value="{{ $score }}" x-model="answers['{{ $criterion['id'] }}']" @checked((string) old('scores.'.$criterion['id']) === (string) $score) @required(!$cycle->frozen_configuration['allow_abstention']) aria-describedby="criterion-help-{{ $criterion['id'] }}" @change="emptyError=false">
                                        <span><strong class="block text-lg">{{ $score }}</strong><span class="text-sm">{{ $label }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($cycle->frozen_configuration['allow_abstention'])<label class="flex cursor-pointer items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm"><input type="radio" name="scores[{{ $criterion['id'] }}]" value="" x-model="answers['{{ $criterion['id'] }}']" @checked(old('scores.'.$criterion['id']) === '')>Não tenho informação suficiente para avaliar este ponto.</label>@endif
                            @error('scores.'.$criterion['id'])<p role="alert" class="text-sm text-rose-700">{{ $message }}</p>@enderror
                        </fieldset>
                    @endforeach
                </div>
                <section x-cloak x-show="reviewing" class="space-y-4">
                    <h3 x-ref="summary" tabindex="-1" class="text-xl font-bold">Confira suas respostas</h3>
                    @foreach ($criteria as $criterion)<p><strong>{{ $criterion['name'] }}:</strong> <span x-text="answers['{{ $criterion['id'] }}'] === '' ? 'Não tenho informação suficiente' : answers['{{ $criterion['id'] }}'] + ' — ' + labels[answers['{{ $criterion['id'] }}']]"></span></p>@endforeach
                    <p class="text-sm text-slate-600">Depois de confirmar, o voto não poderá ser alterado. Suas respostas individuais não aparecem para outros funcionários.</p>
                    <button type="button" class="apple-button--light" @click="reviewing=false">Quero corrigir minhas respostas</button>
                </section>
                <p x-cloak x-show="emptyError" role="alert" class="text-sm text-rose-700">Escolha uma nota em pelo menos uma pergunta para enviar seu voto.</p>
                <button type="submit" class="apple-button" :aria-disabled="sending" x-text="sending ? 'Enviando…' : (reviewing ? 'Confirmar meu voto' : 'Conferir minhas respostas')">Enviar meu voto</button>
            </form>
        @else
            <section class="apple-card space-y-3 p-6" role="status"><h3 class="text-xl font-bold">{{ $voteState['state'] === 'registered' ? 'Sua participação' : 'Sobre esta votação' }}</h3><p>{{ $voteState['message'] }}</p><a class="text-sky-700 underline" href="{{ route('cycles.index') }}">Ver outras votações</a></section>
        @endif
        <details class="apple-card space-y-3 p-5"><summary class="cursor-pointer font-semibold">Como os votos ajudam no reconhecimento?</summary><p class="text-sm">{{ $cycle->isSelection() ? 'Sua avaliação ajuda a priorizar as propostas. A equipe decide quais ideias avançam, considerando viabilidade e recursos. A votação não garante implementação ou bonificação.' : 'Sua avaliação contribui para a decisão sobre o reconhecimento da ideia. A apuração segue as regras deste ciclo e o reconhecimento passa pela aprovação da equipe responsável.' }}</p><a class="text-sm text-sky-700 underline" href="{{ route('cycles.show', $cycle) }}">Consultar as regras desta votação</a></details>
    </div>
</x-app-layout>
