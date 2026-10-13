<x-guest-layout>
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="text-sm font-medium text-slate-700">E-mail</label>
            <input id="email" class="apple-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="seu@email.com">
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="text-sm font-medium text-slate-700">Senha</label>
            <input id="password" class="apple-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between gap-4 pt-2">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" name="remember">
                Lembrar-me
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-sky-600 hover:text-sky-700">Recuperar senha</a>
            @endif
        </div>

        <button type="submit" class="apple-button w-full">Entrar</button>

        <div class="pt-2 text-center text-sm text-slate-600">
            Ainda não tem conta?
            <a href="{{ route('register') }}" class="font-semibold text-slate-900 hover:text-sky-600">Criar conta</a>
        </div>
    </form>
</x-guest-layout>
