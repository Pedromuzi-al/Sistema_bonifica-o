<form data-cycle-purpose="selection" method="POST" action="{{ route('cycles.store') }}" class="apple-card stack-5 p-6" x-data="{ modelId: @js((string) old('technical_model_id', '')), policyId: @js((string) old('bonus_policy_id', '')), models: @js($technicalModels->keyBy('id')), policies: @js($bonusPolicies->keyBy('id')) }" x-on:invalid.capture="$event.target.closest('details')?.setAttribute('open', '')">
    @csrf
    <input type="hidden" name="purpose" value="selection">
    <input type="hidden" name="timezone" value="{{ config('innovation.cycle_timezone') }}">
    <h3 class="text-2xl font-bold">Criar ciclo de seleção de ideias</h3>
    <p>Registre → revise e libere → vote no potencial → selecione → implemente → comprove os resultados.</p>
    <p class="text-sm text-slate-600">Podem participar propostas ainda não implementadas, em execução ou já implementadas. Basta revisar a ideia e usar Liberar para votação, retirando dados pessoais e informações confidenciais.</p>
    <x-workflow-field name="name" label="Nome do ciclo" placeholder="Ex.: Ideias para melhorar nosso trabalho" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-workflow-field name="starts_at" label="Início da votação" type="datetime-local" :value="now()->setTimezone(config('innovation.cycle_timezone'))->addMinutes(10)->format('Y-m-d\TH:i')" />
        <x-workflow-field name="ends_at" label="Fim da votação" type="datetime-local" :value="now()->setTimezone(config('innovation.cycle_timezone'))->addDays(7)->format('Y-m-d\TH:i')" />
        <x-workflow-field name="max_selections" label="Quantas ideias podem avançar?" type="number" value="5" min="1" max="1000" />
        <x-workflow-field name="minimum_votes" label="Mínimo de votos por ideia" type="number" value="1" min="1" max="100000" />
    </div>
    <p class="text-sm text-slate-500">Horários em {{ config('innovation.cycle_timezone') }}. Pode começar e terminar hoje; reserve alguns minutos antes do início para escolher as propostas e confirmar a abertura.</p>
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="stack-2">
            <label for="technical-model" class="block text-sm font-medium">Critério técnico (modelo de perguntas)</label>
            <select id="technical-model" name="technical_model_id" x-model="modelId" required class="block w-full rounded-lg">
                <option value="">Selecione um modelo</option>
                @foreach($technicalModels as $model)<option value="{{ $model->id }}" @selected(old('technical_model_id')==$model->id)>{{ $model->name }} · {{ count($model->criteria) }} pergunta(s)</option>@endforeach
            </select>
            @error('technical_model_id')<p role="alert" class="text-sm text-rose-700">{{ $message }}</p>@enderror
            <p class="text-sm text-slate-600">Define as perguntas e os pesos da votação. Cada pergunta recebe uma nota de 1 a 5.</p>
            <template x-if="models[modelId]"><div class="rounded-lg bg-slate-50 p-3 text-sm"><p x-text="models[modelId].description"></p><template x-for="(criterion, index) in models[modelId].criteria" :key="index"><p class="mt-1" x-text="criterion.name + ' · ' + criterion.weight + '%'"></p></template></div></template>
            <a href="{{ route('financial.technical.index') }}" class="text-sm text-sky-700 underline">Gerenciar critérios técnicos</a>
        </div>
        <div class="stack-2">
            <label for="bonus-policy" class="block text-sm font-medium">Regra de bonificação</label>
            <select id="bonus-policy" name="bonus_policy_id" x-model="policyId" required class="block w-full rounded-lg">
                <option value="">Selecione uma regra</option>
                @foreach($bonusPolicies as $policy)<option value="{{ $policy->id }}" @selected(old('bonus_policy_id')==$policy->id)>{{ $policy->name }} · R$ {{ number_format((float)$policy->reward_amount, 2, ',', '.') }} por ideia</option>@endforeach
            </select>
            @error('bonus_policy_id')<p role="alert" class="text-sm text-rose-700">{{ $message }}</p>@enderror
            <p class="text-sm text-slate-600">Define o valor por ideia, a nota mínima e as regras adicionais. Use uma regra de valor zero para seleção sem dinheiro.</p>
            <template x-if="policies[policyId]"><div class="rounded-lg bg-slate-50 p-3 text-sm"><p x-text="policies[policyId].description"></p><p class="mt-1" x-text="'Nota mínima: ' + policies[policyId].minimum_score + ' de 100'"></p><p class="mt-1" x-text="policies[policyId].allow_abstention ? 'Pode deixar critérios sem nota, respondendo pelo menos um.' : 'Todas as perguntas precisam de nota.'"></p><p class="mt-1" x-text="'Revisar a partir de ' + policies[policyId].anomaly_threshold + ' votos idênticos.'"></p></div></template>
            <a href="{{ route('financial.policies.index') }}" class="text-sm text-sky-700 underline">Gerenciar regras de bonificação</a>
        </div>
    </div>
    @if($technicalModels->isEmpty() || $bonusPolicies->isEmpty())<p class="text-sm text-amber-800" role="status">Cadastre um modelo de critérios e uma regra de bonificação ativos em Regras financeiras antes de continuar.</p>@endif
    <p class="text-sm text-slate-600">A votação organiza as prioridades. A equipe decide quais ideias avançam e aprova as bonificações separadamente. As regras escolhidas ficam preservadas neste ciclo.</p>
    <button class="apple-button">Continuar para escolher as propostas</button>
    <p class="text-sm text-slate-500">Primeiro será salvo um rascunho. Ideias não selecionadas ficam no banco e podem participar de outro ciclo.</p>
</form>
