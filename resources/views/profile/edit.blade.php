<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">{{ $isAdminViewingAnotherUser ? 'Administração de perfil' : 'Perfil' }}</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-slate-900">{{ $isAdminViewingAnotherUser ? 'Configuração de acesso de ' . $user->name : 'Configurações do usuário' }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="apple-card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="apple-card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="apple-card p-4 sm:p-8">
                <div class="max-w-2xl">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold tracking-[-0.04em] text-slate-900">Permissões e perfil</h3>
                        <p class="mt-2 text-sm text-slate-600">Aqui você pode revisar seu perfil e, quando permitido, ajustar as permissões internas do usuário.</p>
                    </div>

                    @if ($canManageRoles)
                        <form method="POST" action="{{ route('profile.permissions', ['user' => $user->id]) }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="role" class="text-sm font-medium text-slate-700">Perfil principal</label>
                                <select id="role" name="role" class="apple-input">
                                    <option value="">Sem perfil específico</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($isAdminViewingAnotherUser)
                                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                                    Você está ajustando as permissões de <span class="font-semibold">{{ $user->name }}</span>.
                                </div>
                            @endif

                            <div>
                                <label class="text-sm font-medium text-slate-700">Permissões extras</label>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" @checked($user->hasPermissionTo($permission->name))>
                                            {{ $permission->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <button type="submit" class="apple-button">Salvar permissões</button>
                        </form>
                    @else
                        <div class="rounded-[22px] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            <p class="font-medium text-slate-800">Seu perfil atual</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($user->roles as $role)
                                    <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white">{{ $role->name }}</span>
                                @empty
                                    <span class="text-slate-500">Sem perfil definido.</span>
                                @endforelse
                            </div>
                            <p class="mt-4">Você pode revisar suas permissões, mas a alteração precisa ser feita por um administrador.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="apple-card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
