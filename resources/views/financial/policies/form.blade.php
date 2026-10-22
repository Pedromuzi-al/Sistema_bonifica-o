<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $policy->exists ? 'Editar regra de bonificação' : 'Nova regra de bonificação' }}</h2></x-slot>
    <div class="mx-auto max-w-3xl stack-5 px-4 py-8">
        <a href="{{ route('financial.policies.index') }}" class="text-sky-700 underline">← Voltar para regras de bonificação</a>
        <form method="POST" action="{{ $policy->exists ? route('financial.policies.update', $policy) : route('financial.policies.store') }}" class="apple-card stack-5 p-6">
            @csrf @if($policy->exists) @method('PATCH') @endif
            <x-workflow-field name="name" label="Nome da regra" :value="$policy->name" maxlength="255" placeholder="Ex.: Reconhecimento de melhorias" />
            <x-workflow-field name="description" label="Como esta regra funciona?" type="textarea" :value="$policy->description" maxlength="2000" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-workflow-field name="reward_amount" label="Valor por ideia selecionada (R$)" type="number" :value="$policy->reward_amount" min="0" max="9999999999" step="0.01" />
                <x-workflow-field name="minimum_score" label="Nota mínima para selecionar (0 a 100)" type="number" :value="$policy->minimum_score" min="0" max="100" step="0.01" />
            </div>
            <p class="text-sm text-slate-600">Zero permite selecionar sem dinheiro. O valor é por ideia, dividido entre os participantes conforme a participação registrada. O limite de ideias é definido no ciclo.</p>
            <x-workflow-field name="anomaly_threshold" label="Votos idênticos para gerar alerta de revisão" type="number" :value="$policy->anomaly_threshold" min="2" max="100000" />
            <label class="block text-sm">Permitir deixar critérios sem nota?<select name="allow_abstention" class="mt-1 block w-full rounded-lg"><option value="0" @selected(!old('allow_abstention', $policy->allow_abstention))>Não, responder todos</option><option value="1" @selected(old('allow_abstention', $policy->allow_abstention))>Sim, responder pelo menos um</option></select></label>
            <label class="block text-sm">Situação<select name="active" class="mt-1 block w-full rounded-lg"><option value="1" @selected(old('active', $policy->active))>Ativa</option><option value="0" @selected(!old('active', $policy->active))>Inativa</option></select></label>
            <p class="text-sm text-slate-500">Alterações valem para novos ciclos. Inativar impede novas escolhas e preserva o histórico.</p>
            <button class="apple-button">{{ $policy->exists ? 'Salvar regra' : 'Cadastrar regra' }}</button>
        </form>
    </div>
</x-app-layout>
