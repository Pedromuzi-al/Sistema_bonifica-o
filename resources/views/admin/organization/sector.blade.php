<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $sector->exists ? 'Editar setor' : 'Novo setor' }}</h2></x-slot>
    <div class="mx-auto max-w-2xl space-y-5 px-4 py-8"><a class="text-sky-700 underline" href="{{ route('admin.sectors.index') }}">Voltar para setores</a>
        <form method="POST" action="{{ $sector->exists ? route('admin.sectors.update', $sector) : route('admin.sectors.store') }}" class="apple-card space-y-5 p-6">@csrf @if($sector->exists) @method('PATCH') @endif
            <x-workflow-field name="name" label="Nome do setor" :value="$sector->name" maxlength="255" /><x-workflow-field name="description" label="Descrição (opcional)" type="textarea" :value="$sector->description" :required="false" maxlength="5000" />
            <input type="hidden" name="is_active" value="0"><label class="flex items-center gap-3"><input name="is_active" type="checkbox" value="1" @checked(old('is_active',$sector->is_active))>Setor ativo</label>
            <p class="text-sm text-slate-600">Um setor inativo não aparece em novas ideias ou novas associações. Os dados anteriores continuam disponíveis.</p><button class="apple-button">Salvar setor</button>
        </form>
    </div>
</x-app-layout>
