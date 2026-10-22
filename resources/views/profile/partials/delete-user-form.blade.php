<section class="stack-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Desativar conta') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('A desativação bloqueia seu acesso. Ideias, avaliações, votos e reconhecimentos serão preservados para manter o histórico corporativo. Solicitações de exclusão ou anonimização seguem o procedimento de retenção da empresa.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Desativar conta') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Tem certeza de que deseja desativar sua conta?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Digite sua senha para confirmar a desativação. Os registros corporativos serão preservados.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Senha') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Senha') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Desativar conta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
