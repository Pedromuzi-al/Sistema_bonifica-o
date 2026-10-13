<nav x-data="{ open: false }" class="site-header sticky top-0 z-50 border-b border-slate-200/80 bg-white/55 backdrop-blur-2xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-20 items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/conted-tech-logo-visible.png') }}" alt="Conted Tech — tecnologia educacional" class="brand-logo h-auto w-56 rounded-xl border border-slate-200/80 bg-white px-3 py-2 object-contain shadow-sm shadow-slate-900/5 sm:w-64">
                </a>
            </div>

            <div class="hidden items-center gap-2 sm:flex">
                <a href="{{ route('dashboard') }}" class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : '' }}">Painel</a>
                <a href="{{ route('ideas.index') }}" class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('ideas.*') ? 'bg-slate-900 text-white' : '' }}">Ideias</a>
                @php($canEvaluate = auth()->user()->can('evaluate', new App\Models\Idea))
                @if ($canEvaluate)
                    <a href="{{ route('evaluation.index') }}" data-permission="true" data-permission-message="Você não tem permissão para acessar a avaliação." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('evaluation.*') ? 'bg-slate-900 text-white' : '' }}">Avaliação</a>
                @else
                    <button type="button" data-permission="false" data-permission-message="Você não tem permissão para acessar a avaliação." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Avaliação</button>
                @endif
                @php($canAccessAdmin = auth()->user()->can('manage-admin'))
                @if ($canAccessAdmin)
                    <a href="{{ route('admin.index') }}" data-permission="true" data-permission-message="Você não tem permissão para acessar o painel administrativo." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('admin.*') ? 'bg-slate-900 text-white' : '' }}">Administração</a>
                @else
                    <button type="button" data-permission="false" data-permission-message="Você não tem permissão para acessar o painel administrativo." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Administração</button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <button type="button" data-theme-toggle aria-label="Ativar modo escuro" title="Ativar modo escuro" aria-pressed="false" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-sky-100">
                    <svg data-theme-icon="moon" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8 8.5 8.5 0 1 0 20.2 15.3Z" /></svg>
                    <svg data-theme-icon="sun" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" /></svg>
                    <span data-theme-label class="hidden lg:inline">Modo escuro</span>
                </button>
                <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-full border border-slate-200 bg-white/70 px-2 py-1.5 sm:flex hover:bg-slate-50 transition-colors">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0faddf] text-sm font-bold text-slate-950">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    <div class="pr-2 text-sm font-medium text-slate-700">{{ Auth::user()->name }}</div>
                </a>

                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/80 p-2 text-slate-600 shadow-sm hover:bg-slate-50 sm:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hidden rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 sm:inline-flex">Sair</button>
                </form>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200/80 bg-white/80 sm:hidden">
        <div class="mx-auto max-w-7xl space-y-2 px-4 py-4">
            <a href="{{ route('dashboard') }}" class="block rounded-2xl px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : '' }}">Painel</a>
            <a href="{{ route('ideas.index') }}" class="block rounded-2xl px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('ideas.*') ? 'bg-slate-900 text-white' : '' }}">Ideias</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full rounded-2xl border border-slate-200 px-3 py-3 text-left text-sm font-medium text-slate-700 hover:bg-slate-100">Sair</button>
            </form>
        </div>
    </div>
</nav>
