<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $model->exists ? 'Editar modelo de critérios' : 'Novo modelo de critérios' }}</h2></x-slot>
    <div class="mx-auto max-w-4xl stack-5 px-4 py-8">
        <a href="{{ route('financial.technical.index') }}" class="text-sky-700 underline">← Voltar para critérios técnicos</a>
        <form method="POST" action="{{ $model->exists ? route('financial.technical.update', $model) : route('financial.technical.store') }}" class="apple-card stack-5 p-6"
            x-data="{ criteria: @js(old('criteria', $model->criteria)), get total() { return this.criteria.reduce((sum, item) => sum + Number(item.weight || 0), 0) } }">
            @csrf @if($model->exists) @method('PATCH') @endif
            <x-workflow-field name="name" label="Nome do modelo" :value="$model->name" maxlength="255" placeholder="Ex.: Melhorias internas" />
            <x-workflow-field name="description" label="Quando usar este modelo?" type="textarea" :value="$model->description" maxlength="2000" />
            <div class="stack-4"><h3 class="text-lg font-semibold">Perguntas da votação</h3><p class="text-sm text-slate-600">O funcionário responde de 1 a 5. Defina uma orientação clara e distribua 100% entre as perguntas.</p>
                <template x-for="(criterion, index) in criteria" :key="index">
                    <fieldset class="stack-3 rounded-xl border p-4"><legend class="px-1 font-semibold" x-text="'Pergunta ' + (index + 1)"></legend>
                        <label class="block text-sm">Nome da pergunta<input required maxlength="255" x-model="criterion.name" :name="`criteria[${index}][name]`" class="mt-1 block w-full rounded-lg"></label>
                        <label class="block text-sm">Orientação para dar a nota<textarea required maxlength="1000" x-model="criterion.description" :name="`criteria[${index}][description]`" class="mt-1 block w-full rounded-lg" rows="2"></textarea></label>
                        <label class="block text-sm">Peso (%)<input required type="number" min="0.01" max="100" step="0.01" x-model="criterion.weight" :name="`criteria[${index}][weight]`" class="mt-1 block w-full rounded-lg"></label>
                        <button type="button" class="text-sm text-rose-700 underline" @click="criteria.splice(index, 1)" :disabled="criteria.length <= 1">Remover pergunta</button>
                    </fieldset>
                </template>
                <div class="flex flex-wrap items-center gap-4"><button type="button" class="apple-button--light" @click="criteria.push({ name: '', description: '', weight: '' })" :disabled="criteria.length >= 20">Adicionar pergunta</button><p class="text-sm font-semibold" role="status">Total dos pesos: <span x-text="total.toFixed(2)"></span>%</p></div>
                @error('criteria')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
            </div>
            <label class="block text-sm">Situação<select name="active" class="mt-1 block w-full rounded-lg"><option value="1" @selected(old('active', $model->active))>Ativo</option><option value="0" @selected(!old('active', $model->active))>Inativo</option></select></label>
            <p class="text-sm text-slate-500">Alterar ou inativar este modelo não muda as perguntas nem os pesos dos ciclos já criados.</p>
            <button class="apple-button">{{ $model->exists ? 'Salvar modelo' : 'Cadastrar modelo' }}</button>
        </form>
    </div>
</x-app-layout>
