<x-guest-layout>
    @if (config('innovation.invitation_delivery') === 'manual')
        <h1 class="mb-4 text-xl font-bold">Recuperar acesso</h1>
        <p class="mb-4 text-sm text-gray-600">Solicite à administração a redefinição da sua senha. Informe seu nome e o e-mail cadastrado. Após conferir sua identidade, a administração ajudará você a recuperar o acesso.</p>
        <a class="underline" href="{{ route('login') }}">Voltar para entrar</a>
    @else
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Esqueceu sua senha? Sem problema. Apenas informe seu endereço de e-mail e enviaremos um link para redefinição de senha para que você possa escolher uma nova.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('E-mail')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Enviar link de redefinição') }}
            </x-primary-button>
        </div>
    </form>
    @endif
</x-guest-layout>
