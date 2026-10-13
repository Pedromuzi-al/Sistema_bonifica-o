<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="text-sm font-medium text-slate-700">Nome</label>
            <input id="name" class="apple-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Seu nome completo">
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="text-sm font-medium text-slate-700">E-mail</label>
            <input id="email" class="apple-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="seu@email.com">
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="text-sm font-medium text-slate-700">Senha</label>
            <input id="password" class="apple-input" type="password" name="password" required autocomplete="new-password" placeholder="••••••••">
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirmar senha</label>
            <input id="password_confirmation" class="apple-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
            @error('password_confirmation')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="apple-button w-full">Criar conta</button>

        <div class="pt-2 text-center text-sm text-slate-600">
            Já possui uma conta?
            <a href="{{ route('login') }}" class="font-semibold text-slate-900 hover:text-sky-600">Entrar</a>
        </div>
    </form>
</x-guest-layout>
