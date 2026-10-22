<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $criterion->exists ? 'Editar critério técnico' : 'Novo critério técnico' }}</h2></x-slot>
    <div class="mx-auto max-w-3xl stack-5 px-4 py-8">
        <a href="{{ route('admin.criteria.index') }}" class="text-sky-700 underline">← Voltar para critérios técnicos</a>
        <form class="apple-card stack-5 p-6" method="POST" action="{{ $criterion->exists ? route('criteria.update',$criterion) : route('criteria.store') }}">@csrf @if($criterion->exists) @method('PATCH') @endif
            <x-workflow-field name="name" label="Nome" :value="$criterion->name" maxlength="255" />
            <x-workflow-field name="description" label="Descrição e orientação das notas" type="textarea" :value="$criterion->description" maxlength="1000" />
            <x-workflow-field name="weight" label="Peso relativo" type="number" min="0.01" max="100" step="0.01" :value="$criterion->weight" />
            @if($criterion->exists)<label class="block">Situação<select name="active" class="mt-1 block w-full rounded-lg"><option value="1" @selected(old('active',$criterion->active)==1)>Ativo</option><option value="0" @selected(old('active',$criterion->active)==0)>Inativo</option></select></label>@endif
            <p class="text-sm text-slate-600">Alterações valem para novos pareceres. Notas, descrições e pesos das avaliações anteriores continuam preservados.</p>
            <button class="apple-button">{{ $criterion->exists ? 'Salvar critério' : 'Cadastrar critério' }}</button>
        </form>
    </div>
</x-app-layout>
