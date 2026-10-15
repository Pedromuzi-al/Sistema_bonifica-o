<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Confirme seu e-mail para continuar. Abra a mensagem enviada para o seu endereço e clique em “Confirmar meu e-mail”. Se não encontrar, confira também a pasta de spam.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('Um novo link de verificação foi enviado para o endereço de e-mail informado durante o cadastro.') }}
        </div>
    @endif

    <p class="mb-4 text-sm text-gray-600">Endereço cadastrado: <strong>{{ auth()->user()->email }}</strong>. <a href="{{ route('profile.edit') }}" class="underline">Corrigir meu e-mail</a></p>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Reenviar e-mail de verificação') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                {{ __('Sair') }}
            </button>
        </form>
    </div>
</x-guest-layout>
