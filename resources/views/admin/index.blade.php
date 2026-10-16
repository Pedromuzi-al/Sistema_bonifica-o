<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Visão de gestão</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-slate-900 sm:text-4xl">Administração</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Acompanhe o andamento das ideias e gerencie os acessos da equipe em um só lugar.</p>
            </div>
            <span class="apple-pill">Área restrita</span>
        </div>
    </x-slot>

    @php
        $statusLabels = \App\Models\Idea::LABELS;
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="apple-card flex flex-wrap gap-4 p-6"><a href="{{ route('admin.users.index') }}" class="apple-button">Pessoas e acessos</a><a href="{{ route('admin.organization') }}" class="apple-button--light">Setores e equipes</a><a href="{{ route('cycles.index') }}" class="apple-button">Configurar ciclos e bonificações</a><a href="{{ route('reports.index') }}" class="apple-button--light">Indicadores e CSV</a>@if(auth()->user()->hasSystemPermission('view_audit_logs'))<a href="{{ route('audit.index') }}" class="apple-button--light">Auditoria restrita</a>@endif</section>
            <form class="apple-card space-y-3 p-6" method="POST" action="{{ route('criteria.store') }}">@csrf<h3 class="text-xl font-bold">Novo critério técnico</h3><x-workflow-field name="name" label="Nome" /><x-workflow-field name="description" label="Descrição e orientação das notas" /><x-workflow-field name="weight" label="Peso relativo" type="number" min="0.01" max="100" step="0.01" /><button class="apple-button">Cadastrar critério</button></form>
            <section aria-label="Resumo das ideias" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Ideias recebidas', 'value' => $stats['total'], 'detail' => 'Total na plataforma', 'tone' => 'slate'],
                    ['label' => 'Aprovadas', 'value' => $stats['approved'], 'detail' => 'Prontas para avançar', 'tone' => 'emerald'],
                    ['label' => 'Implementadas', 'value' => $stats['implemented'], 'detail' => 'Em operação', 'tone' => 'sky'],
                    ['label' => 'Concluídas', 'value' => $stats['completed'], 'detail' => 'Resultados registrados', 'tone' => 'sky'],
                ] as $stat)
                    <article class="apple-card p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                                <p class="mt-3 text-4xl font-black tracking-[-0.06em] text-slate-900">{{ $stat['value'] }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ $stat['detail'] }}</p>
                            </div>
                            <span class="mt-1 h-3 w-3 rounded-full
                                {{ $stat['tone'] === 'emerald' ? 'bg-emerald-500 shadow-[0_0_18px_rgba(16,185,129,0.35)]' : '' }}
                                {{ $stat['tone'] === 'sky' ? 'bg-sky-500 shadow-[0_0_18px_rgba(14,165,233,0.35)]' : '' }}
                                {{ $stat['tone'] === 'slate' ? 'bg-slate-400' : '' }}"></span>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                <article class="apple-card p-5 sm:p-7">
                    <div class="mb-5 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Distribuição</p>
                            <h3 class="mt-1 text-xl font-bold tracking-[-0.04em] text-slate-900">Ideias por setor</h3>
                        </div>
                        <span class="apple-pill">{{ $sectors->count() }} setores</span>
                    </div>

                    <div class="divide-y divide-slate-200/70">
                        @forelse ($sectors as $sector)
                            <div class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-slate-600">
                                        {{ strtoupper(substr($sector->name, 0, 1)) }}
                                    </span>
                                    <span class="truncate text-sm font-semibold text-slate-800">{{ $sector->name }}</span>
                                </div>
                                <span class="shrink-0 text-sm text-slate-500">{{ $sector->ideas_as_origin_count }} {{ $sector->ideas_as_origin_count === 1 ? 'ideia' : 'ideias' }}</span>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-slate-500">Nenhum setor cadastrado.</p>
                        @endforelse
                    </div>
                </article>

                <article class="apple-card p-5 sm:p-7">
                    <div class="mb-5 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Atividade recente</p>
                            <h3 class="mt-1 text-xl font-bold tracking-[-0.04em] text-slate-900">Últimas ideias</h3>
                        </div>
                        <a href="{{ route('ideas.index') }}" class="text-sm font-semibold text-sky-600 hover:text-sky-700">Ver todas</a>
                    </div>

                    <div class="divide-y divide-slate-200/70">
                        @forelse ($ideas as $idea)
                            <a href="{{ route('ideas.show', $idea) }}" class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $idea->title }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500">{{ $idea->originSector?->name ?? 'Setor não informado' }} · {{ $idea->visibleAuthorName(auth()->user()) }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                    {{ $statusLabels[$idea->status] ?? $idea->status }}
                                </span>
                            </a>
                        @empty
                            <p class="py-6 text-sm text-slate-500">Ainda não há ideias para exibir.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="apple-card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-200/70 px-5 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-7">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Controle de acesso</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.04em] text-slate-900">Pessoas e permissões</h3>
                        <p class="mt-1 text-sm text-slate-500">Selecione uma pessoa para revisar o perfil e ajustar o acesso.</p>
                    </div>
                    <span class="apple-pill">{{ $users->count() }} {{ $users->count() === 1 ? 'pessoa' : 'pessoas' }}</span>
                </div>

                <div class="divide-y divide-slate-200/70">
                    @forelse ($users as $user)
                        <div class="flex flex-col gap-4 px-5 py-4 transition-colors hover:bg-white/50 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0faddf] text-sm font-bold text-slate-950 shadow-lg shadow-sky-500/20">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                @forelse ($user->roles as $role)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">{{ $role->name }}</span>
                                @empty
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-700">Sem perfil</span>
                                @endforelse

                                <a href="{{ route('admin.users.edit', $user) }}" class="apple-button--light !px-4 !py-2 !text-xs">
                                    Gerenciar acesso
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-500">Nenhuma pessoa cadastrada.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
