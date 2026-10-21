<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">Compartilhe sua ideia</h2>
        <p class="mt-2 text-slate-600">Conte o que pode melhorar no seu dia a dia. Não precisa escrever um projeto técnico.</p>
    </x-slot>
    @php
        $selectedSectors = array_map('strval', old('beneficiary_sectors', []));
        $defaultStep = $errors->hasAny(['title', 'problem_description', 'solution_description']) ? 1 : ($errors->hasAny(['origin_sector_id', 'beneficiary_sectors', 'beneficiary_sectors.*']) ? 2 : ($errors->any() ? 3 : 1));
    @endphp
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-8">
        <p class="rounded-xl bg-sky-50 p-4 text-sm text-sky-900">Seu nome fica protegido dos demais funcionários. Somente pessoas com autorização podem consultar a autoria.</p>
        <form method="POST" action="{{ route('ideas.review') }}" class="apple-card space-y-6 p-5 sm:p-8" x-data="ideaWizard({{ $defaultStep }})" :novalidate="true" @submit="submit($event)">
            @csrf
            <nav aria-label="Etapas do cadastro" class="grid grid-cols-3 gap-2 text-sm">
                @foreach ([1 => 'Sua ideia', 2 => 'Quem ajuda', 3 => 'Benefícios'] as $step => $label)
                    <div class="rounded-xl border p-3" :class="step === {{ $step }} ? 'border-sky-500 bg-sky-50 text-sky-900' : 'border-slate-200 text-slate-500'" :aria-current="step === {{ $step }} ? 'step' : null"><span class="block font-bold">{{ $step }}</span>{{ $label }}</div>
                @endforeach
            </nav>
            <p x-cloak aria-live="polite" class="text-sm text-slate-500" x-text="'Passo ' + step + ' de 3. Você vai conferir tudo antes de enviar.'"></p>
            <fieldset data-step="1" x-show="step === 1" class="space-y-5">
                <legend class="mb-4 text-xl font-bold" tabindex="-1">1. O que você quer melhorar?</legend>
                <x-workflow-field name="title" label="Dê um nome para a ideia" placeholder="Ex.: Organizar os pedidos em uma fila única" maxlength="255" />
                <x-workflow-field name="problem_description" label="O que acontece hoje e atrapalha o trabalho?" type="textarea" placeholder="Ex.: Os pedidos chegam por vários canais e alguns acabam esquecidos." maxlength="20000" />
                <x-workflow-field name="solution_description" label="Como você acha que isso poderia melhorar?" type="textarea" placeholder="Ex.: Reunir todos os pedidos em uma lista que a equipe consiga acompanhar." maxlength="20000" />
                <p class="text-sm text-slate-500">Uma explicação simples, com suas palavras, já ajuda a equipe a entender.</p>
            </fieldset>
            <fieldset data-step="2" x-show="step === 2" class="space-y-5">
                <legend class="mb-4 text-xl font-bold" tabindex="-1">2. Onde a ideia faz diferença?</legend>
                <label for="origin-sector" class="block text-sm font-medium">Em qual setor você percebeu o problema?</label>
                <select id="origin-sector" name="origin_sector_id" class="block w-full rounded-lg border-slate-300" required>
                    <option value="">Escolha um setor</option>
                    @foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(old('origin_sector_id', auth()->user()->sector_id) == $sector->id)>{{ $sector->name }}</option>@endforeach
                </select>
                <fieldset class="space-y-3 rounded-xl border border-slate-200 p-4" x-data="{ selected: @js($selectedSectors), all: @js($sectors->pluck('id')->map(fn ($id) => (string) $id)->values()) }">
                    <legend class="px-1 text-sm font-semibold">Quem pode se beneficiar? Marque um ou mais setores.</legend>
                    <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3"><input type="checkbox" :checked="all.length > 0 && selected.length === all.length" @change="selected = $event.target.checked ? [...all] : []; $dispatch('change')"> Todos os setores</label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($sectors as $sector)<label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3"><input type="checkbox" name="beneficiary_sectors[]" value="{{ $sector->id }}" x-model="selected" @checked(in_array((string) $sector->id, $selectedSectors, true)) @change="$el.setCustomValidity('')">{{ $sector->name }}</label>@endforeach
                    </div>
                    <p class="text-sm text-rose-700" x-show="sectorError" x-cloak role="alert">Marque pelo menos um setor que pode se beneficiar.</p>
                </fieldset>
            </fieldset>
            <fieldset data-step="3" x-show="step === 3" class="space-y-5">
                <legend class="mb-4 text-xl font-bold" tabindex="-1">3. O que pode melhorar com essa ideia?</legend>
                <x-workflow-field name="expected_results" label="Que benefícios você espera? (opcional)" :required="false" type="textarea" placeholder="Ex.: Menos pedidos esquecidos e menos tempo procurando informações. Não precisa calcular valores agora." maxlength="20000" />
                <x-workflow-field name="resources_needed" label="O que seria necessário para colocar em prática? (opcional)" :required="false" type="textarea" placeholder="Ex.: Uma planilha e uma conversa com a equipe. Se ainda não souber, escreva isso aqui." maxlength="20000" />
                <details class="rounded-xl border border-slate-200 p-4" @if(old('additional_information')) open @endif>
                    <summary class="cursor-pointer font-medium">Quer acrescentar alguma coisa? (opcional)</summary>
                    <div class="mt-3"><x-workflow-field name="additional_information" label="Exemplos ou observações" type="textarea" :required="false" maxlength="20000" /></div>
                </details>
                <p class="text-sm text-slate-500">Esses benefícios ainda são uma expectativa. Os resultados reais serão medidos depois da implementação.</p>
            </fieldset>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('ideas.index', ['mine' => 1]) }}" class="text-sm text-slate-600 underline">Voltar para minhas ideias</a>
                <div class="flex gap-3">
                    <button type="button" x-cloak x-show="step > 1" @click="move(step - 1)" class="apple-button--light">Voltar</button>
                    <button type="button" x-cloak x-show="step < 3" @click="next()" class="apple-button">Continuar</button>
                    <button type="submit" x-show="step === 3" class="apple-button">Conferir minha ideia</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
