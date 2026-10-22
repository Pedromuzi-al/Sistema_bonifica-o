<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">{{ $user->exists ? 'Gerenciar '.$user->name : 'Cadastrar pessoa' }}</h2></x-slot>
    <div class="mx-auto max-w-3xl stack-5 px-4 py-8">
        <a class="text-sky-700 underline" href="{{ route('admin.users.index') }}">Voltar para pessoas</a>

        @if(! $user->anonymized_at)
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="apple-card stack-5 p-6">@csrf @if($user->exists) @method('PATCH') @endif
            <h3 class="text-xl font-bold">Dados da pessoa</h3>
            <x-workflow-field name="name" label="Nome completo" :value="$user->name" maxlength="255" /><x-workflow-field name="email" label="E-mail" type="email" :value="$user->email" maxlength="255" />
            @if (!$user->exists)<p class="text-sm text-slate-600">{{ config('innovation.invitation_delivery') === 'email' ? 'A pessoa receberá um convite por e-mail para definir a própria senha e confirmar o endereço.' : 'Após cadastrar, defina a senha na página da pessoa para liberar o primeiro acesso. O e-mail será usado para entrar.' }}</p>@endif
            <label class="block">Setor<select name="sector_id" class="block w-full rounded-lg"><option value="">Sem setor</option>@foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(old('sector_id',$user->sector_id)==$sector->id)>{{ $sector->name }}{{ !$sector->is_active ? ' (inativo; associação existente)' : '' }}</option>@endforeach</select></label>
            <fieldset class="stack-2"><legend class="mb-2 font-semibold">Equipes (opcional)</legend>@forelse ($teams as $team)<label class="flex items-center gap-3 rounded-lg border p-3"><input type="checkbox" name="teams[]" value="{{ $team->id }}" @checked(in_array($team->id, old('teams', array_key_exists('name', session()->getOldInput()) ? [] : ($user->exists ? $user->teams->modelKeys() : []))))>{{ $team->name }}{{ !$team->is_active ? ' (inativa)' : '' }}</label>@empty<p class="text-sm">Nenhuma equipe cadastrada. Você pode organizar isso depois.</p>@endforelse</fieldset>
            <h3 class="border-t pt-4 text-xl font-bold">O que essa pessoa pode fazer?</h3>
            <label class="block">Perfil de acesso<select name="role" class="block w-full rounded-lg"><option value="">Sem perfil — aguarda liberação</option>@foreach ($roles as $role)<option value="{{ $role->name }}" @selected(old('role', $user->exists ? $user->getRoleNames()->first() : 'employee') === $role->name)>{{ \App\Models\User::ROLE_LABELS[$role->name] ?? $role->name }}</option>@endforeach</select></label>
            <details class="rounded-xl border p-4" @if($errors->has('permissions.*')) open @endif><summary class="cursor-pointer font-semibold">Permissões adicionais</summary><p class="my-3 text-sm">O perfil já inclui permissões. Marque aqui apenas acessos extras. Desmarcar um acesso extra não remove uma permissão que vem do perfil.</p>@foreach ($permissions as $permission)<label class="my-2 flex items-center gap-3"><input name="permissions[]" type="checkbox" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', array_key_exists('name', session()->getOldInput()) ? [] : ($user->exists ? $user->getDirectPermissions()->pluck('name')->all() : []))))>{{ \App\Models\User::PERMISSION_LABELS[$permission->name] ?? $permission->name }}</label>@endforeach</details>
            <button class="apple-button">{{ $user->exists ? 'Salvar dados e acesso' : (config('innovation.invitation_delivery') === 'email' ? 'Cadastrar e enviar convite' : 'Cadastrar pessoa') }}</button>
        </form>
        @endif
        @if ($user->exists)
        <section class="apple-card stack-3 p-6"><h3 class="text-xl font-bold">Situação da conta</h3><p>{{ $user->trashed() ? 'Desativada' : ($user->blocked_at ? 'Bloqueada desde '.$user->blocked_at->format('d/m/Y H:i') : 'Ativa') }}</p>@if($user->block_reason)<p>Motivo do bloqueio: {{ $user->block_reason }}</p>@endif<p>{{ $user->invitation_pending ? 'Primeiro acesso pendente: defina uma senha para liberar a conta.' : 'Senha de acesso definida.' }} {{ config('innovation.email_verification_required') ? ($user->hasVerifiedEmail() ? 'E-mail confirmado.' : 'Aguardando confirmação de e-mail pelo titular.') : 'Confirmação de e-mail dispensada nesta versão.' }}</p>
            @if(config('innovation.invitation_delivery') === 'email' && !$user->hasVerifiedEmail() && $user->isAvailable())<form method="POST" action="{{ route('admin.users.verification', $user) }}">@csrf<button class="apple-button--light">{{ $user->invitation_pending ? 'Reenviar convite de primeiro acesso' : 'Reenviar confirmação de e-mail' }}</button></form>@endif
        </section>
        @if ($user->isAvailable())
        <section class="apple-card stack-4 p-6">
                <h3 class="text-xl font-bold">Senha de acesso</h3>
                <p>Defina uma senha para liberar o primeiro acesso ou ajudar a pessoa que esqueceu a senha.</p>
                <p class="text-sm text-slate-600">Após salvar, as sessões anteriores serão encerradas. Informe a nova senha diretamente à pessoa para que ela possa entrar novamente.</p>
                <details class="rounded-xl border p-4" @if($errors->has('password')) open @endif>
                    <summary class="cursor-pointer font-semibold">Redefinir senha</summary>
                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-4 stack-4" x-data="{ showPasswords: false }">
                        @csrf
                        <p class="text-sm text-slate-600">{{ app()->isProduction() ? 'Use pelo menos 12 caracteres, com letras maiúsculas, minúsculas e números.' : 'Use pelo menos 8 caracteres.' }}</p>
                        <div>
                            <label for="recovery-password" class="block text-sm font-medium">Nova senha</label>
                            <input id="recovery-password" name="password" type="password" :type="showPasswords ? 'text' : 'password'" autocomplete="new-password" class="mt-1 block w-full rounded-lg border-slate-300" required>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <label for="recovery-password-confirmation" class="block text-sm font-medium">Confirmar senha</label>
                            <input id="recovery-password-confirmation" name="password_confirmation" type="password" :type="showPasswords ? 'text' : 'password'" autocomplete="new-password" class="mt-1 block w-full rounded-lg border-slate-300" required>
                        </div>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="showPasswords">Mostrar nova senha</label>
                        <div class="flex flex-wrap gap-3">
                            <button class="apple-button">Concluir</button>
                            <button type="button" class="apple-button--light" @click="$event.currentTarget.form.reset(); showPasswords = false; const panel = $event.currentTarget.closest('details'); panel.open = false; panel.querySelector('summary').focus()">Cancelar</button>
                        </div>
                    </form>
                </details>
        </section>
        @endif
        @if ($user->anonymized_at)
            <p class="apple-card p-6">Esta conta foi anonimizada e permanece somente para preservar o histórico. Seus dados e acessos não podem ser alterados ou reativados.</p>
        @elseif (!$user->isAvailable())
            <form method="POST" action="{{ route('admin.users.reactivate', $user) }}" class="apple-card stack-4 p-6">@csrf<h3 class="text-xl font-bold">Reativar acesso</h3><p>O histórico e as associações são preservados. Confira o perfil de acesso antes de liberar a pessoa.</p><x-workflow-field name="reason" label="Por que a conta está sendo reativada?" type="textarea" /><button class="apple-button">Reativar conta</button></form>
        @elseif ($user->id !== auth()->id())
            <details class="apple-card p-6"><summary class="cursor-pointer font-semibold text-rose-700">Bloquear acesso desta pessoa</summary><form method="POST" action="{{ route('admin.users.block', $user) }}" class="mt-4 stack-4">@csrf<p>O acesso e as sessões serão interrompidos. Ideias, votos e avaliações continuam no histórico.</p><x-workflow-field name="reason" label="Motivo obrigatório do bloqueio" type="textarea" /><button class="apple-button--light">Confirmar bloqueio</button></form></details>
        @else<p class="text-sm text-slate-500">Você não pode bloquear sua própria conta. O sistema também protege o acesso do último administrador.</p>@endif
        @endif
    </div>
</x-app-layout>
