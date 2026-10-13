<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">
                {{ $isAdminViewingAnotherUser ? 'Gestão de pessoas' : 'Sua conta' }}
            </p>
            <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-slate-900 sm:text-4xl">
                {{ $isAdminViewingAnotherUser ? 'Acesso de ' . $user->name : 'Perfil e configurações' }}
            </h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                {{ $isAdminViewingAnotherUser ? 'Revise as informações e defina o nível de acesso desta pessoa.' : 'Gerencie suas informações pessoais, segurança e permissões.' }}
            </p>
        </div>
    </x-slot>

    @php
        $roleLabels = [
            'employee' => 'Colaborador',
            'evaluator' => 'Avaliador',
            'admin' => 'Administrador',
        ];
        $permissionLabels = [
            'create_ideas' => 'Criar ideias',
            'vote_ideas' => 'Votar em ideias',
            'evaluate_ideas' => 'Avaliar ideias',
            'manage_admin' => 'Gerenciar administração',
        ];
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="apple-card flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-7">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#0faddf] text-2xl font-bold text-slate-950 shadow-xl shadow-sky-500/20">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-xl font-bold tracking-[-0.04em] text-slate-900">{{ $user->name }}</h3>
                        <p class="mt-1 truncate text-sm text-slate-500">{{ $user->email }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($user->roles as $role)
                                <span class="rounded-full bg-slate-900 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white">
                                    {{ $roleLabels[$role->name] ?? $role->name }}
                                </span>
                            @empty
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-700">Sem perfil definido</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                @if ($isAdminViewingAnotherUser)
                    <a href="{{ route('admin.index') }}" class="apple-button--light shrink-0 !px-5 !py-2.5">Voltar à administração</a>
                @endif
            </section>

            <section class="apple-card p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Dados da conta</p>
                    <h3 class="mt-1 text-xl font-bold tracking-[-0.04em] text-slate-900">Informações pessoais</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $isAdminViewingAnotherUser ? 'Atualize o nome ou o e-mail associado a esta conta.' : 'Mantenha seus dados de contato atualizados.' }}
                    </p>
                </div>
                <div class="max-w-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </section>

            @if (! $isAdminViewingAnotherUser)
                <section class="apple-card p-5 sm:p-8">
                    <div class="mb-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Segurança</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.04em] text-slate-900">Senha da conta</h3>
                        <p class="mt-1 text-sm text-slate-500">Use uma senha forte e exclusiva para proteger sua conta.</p>
                    </div>
                    <div class="max-w-2xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </section>
            @endif

            <section class="apple-card p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Controle de acesso</p>
                    <h3 class="mt-1 text-xl font-bold tracking-[-0.04em] text-slate-900">Perfil e permissões</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $canManageRoles ? 'Escolha o perfil principal e ajuste permissões específicas.' : 'Estas permissões determinam quais recursos você pode utilizar.' }}
                    </p>
                </div>

                @if ($canManageRoles)
                    <form method="POST" action="{{ route('profile.permissions', ['user' => $user->id]) }}" class="max-w-3xl space-y-6">
                        @csrf

                        <div>
                            <label for="role" class="text-sm font-semibold text-slate-700">Perfil principal</label>
                            <select id="role" name="role" class="apple-input">
                                <option value="">Sem perfil específico</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>
                                        {{ $roleLabels[$role->name] ?? ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($isAdminViewingAnotherUser)
                            <div class="flex gap-3 rounded-2xl border border-sky-100 bg-sky-50/80 p-4 text-sm leading-6 text-sky-900">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sky-100 font-bold text-sky-700">i</span>
                                <p>As alterações abaixo serão aplicadas ao acesso de <span class="font-semibold">{{ $user->name }}</span>. As configurações pessoais e a senha desta pessoa não são alteradas aqui.</p>
                            </div>
                        @endif

                        <fieldset>
                            <legend class="text-sm font-semibold text-slate-700">Permissões adicionais</legend>
                            <p class="mt-1 text-xs text-slate-500">Permissões concedidas além das incluídas no perfil principal.</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($permissions as $permission)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white/70 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-sky-200 hover:bg-sky-50/60">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" @checked($user->getDirectPermissions()->contains('id', $permission->id))>
                                        {{ $permissionLabels[$permission->name] ?? $permission->name }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <button type="submit" class="apple-button--primary">Salvar permissões</button>
                    </form>
                @else
                    <div class="space-y-5">
                        <div class="flex flex-wrap gap-2">
                            @forelse ($user->roles as $role)
                                <span class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">
                                    {{ $roleLabels[$role->name] ?? ucfirst($role->name) }}
                                </span>
                            @empty
                                <span class="text-sm text-slate-500">Nenhum perfil atribuído.</span>
                            @endforelse
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-slate-700">Ações permitidas</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($user->getAllPermissions() as $permission)
                                    <span class="rounded-full border border-slate-200 bg-white/70 px-3 py-1.5 text-xs font-medium text-slate-600">
                                        {{ $permissionLabels[$permission->name] ?? $permission->name }}
                                    </span>
                                @empty
                                    <span class="text-sm text-slate-500">Nenhuma permissão atribuída no momento.</span>
                                @endforelse
                            </div>
                        </div>

                        <p class="rounded-2xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                            Para alterar seu perfil ou suas permissões, solicite ajuda a um administrador.
                        </p>
                    </div>
                @endif
            </section>

            @if (! $isAdminViewingAnotherUser)
                <section class="apple-card border-rose-100 p-5 sm:p-8">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-500">Zona de atenção</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.04em] text-slate-900">Excluir conta</h3>
                        <p class="mt-1 text-sm text-slate-500">Esta ação é permanente e remove seus dados da plataforma.</p>
                    </div>
                    <div class="max-w-2xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
