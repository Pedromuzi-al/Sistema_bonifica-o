<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $team->exists ? 'Editar equipe' : 'Nova equipe' }}</h2></x-slot>
    <div class="mx-auto max-w-3xl stack-5 px-4 py-8"><a class="text-sky-700 underline" href="{{ route('admin.organization') }}">Voltar para setores e equipes</a>
        <form method="POST" action="{{ $team->exists ? route('admin.teams.update', $team) : route('admin.teams.store') }}" class="apple-card stack-5 p-6">@csrf @if($team->exists) @method('PATCH') @endif
            <x-workflow-field name="name" label="Nome da equipe" :value="$team->name" maxlength="255" /><x-workflow-field name="description" label="Objetivo da equipe (opcional)" type="textarea" :value="$team->description" :required="false" maxlength="5000" />
            <label class="block">Setor de referência (opcional)<select name="sector_id" class="block w-full rounded-lg"><option value="">Equipe entre setores</option>@foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(old('sector_id',$team->sector_id)==$sector->id)>{{ $sector->name }}{{ !$sector->is_active ? ' (inativo)' : '' }}</option>@endforeach</select></label>
            <input type="hidden" name="is_active" value="0"><label class="flex items-center gap-3"><input name="is_active" type="checkbox" value="1" @checked(old('is_active',$team->is_active))>Equipe ativa</label>
            <fieldset class="stack-2"><legend class="mb-3 font-semibold">Quem participa?</legend>@forelse ($users as $person)<label class="flex items-center gap-3 rounded-lg border p-3"><input type="checkbox" name="members[]" value="{{ $person->id }}" @checked(in_array($person->id, old('members', session()->hasOldInput() ? [] : ($team->exists ? $team->users->modelKeys() : []))))>{{ $person->name }}{{ $person->trashed() ? ' (conta desativada; apenas associações existentes)' : ($person->blocked_at ? ' (acesso bloqueado)' : '') }}</label>@empty<p>Cadastre pessoas antes de adicionar participantes.</p>@endforelse</fieldset>
            <p class="text-sm text-slate-600">Equipes inativas mantêm seus participantes e não aceitam novas associações pela gestão de pessoas. O acesso de cada pessoa depende do seu perfil e da situação da conta.</p><button class="apple-button">Salvar equipe e participantes</button>
        </form>
    </div>
</x-app-layout>
