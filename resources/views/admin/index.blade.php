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
            <section aria-label="Áreas da administração" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    ['title'=>'Setores','icon'=>'home','count'=>$moduleCounts['sectors'],'description'=>'Setores ativos e inativos e seus vínculos.','route'=>'admin.sectors.index'],
                    ['title'=>'Pessoas e acessos','icon'=>'user','count'=>$moduleCounts['users'],'description'=>'Cadastro, perfis, permissões, bloqueio e reativação.','route'=>'admin.users.index'],
                    ['title'=>'Critérios técnicos','icon'=>'check','count'=>$moduleCounts['criteria'],'description'=>'Critérios de avaliação, pesos e situação.','route'=>'admin.criteria.index'],
                    ['title'=>'Equipes','icon'=>'settings','count'=>$moduleCounts['teams'],'description'=>'Organização das equipes e participantes.','route'=>'admin.organization'],
                ] as $module)
                    <a href="{{ route($module['route']) }}" class="apple-card block space-y-3 p-5"><x-sidebar-icon :name="$module['icon']" /><p class="text-3xl font-bold">{{ $module['count'] }}</p><h3 class="font-bold">{{ $module['title'] }}</h3><p class="text-sm text-slate-600">{{ $module['description'] }}</p></a>
                @endforeach
            </section>
            @if(auth()->user()->hasSystemPermission('view_audit_logs'))<a href="{{ route('admin.privacy') }}" class="apple-button--light">Solicitações de privacidade</a>@endif
            <section aria-label="Resumo das ideias" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Ideias recebidas', 'value' => $stats['total'], 'detail' => 'Total na plataforma', 'tone' => 'slate'],
                    ['label' => 'Aprovadas', 'value' => $stats['approved'], 'detail' => 'Prontas para avançar', 'tone' => 'emerald'],
                    ['label' => 'Implementadas', 'value' => $stats['implemented'], 'detail' => 'Em operação', 'tone' => 'sky'],
                    ['label' => 'Concluídas', 'value' => $stats['completed'], 'detail' => 'Resultados registrados', 'tone' => 'sky'],
                ] as $stat)
                    <x-stat-card :label="$stat['label']" :value="$stat['value']" :description="$stat['detail']" :tone="$stat['tone'] === 'slate' ? 'brand' : $stat['tone']" icon="chart" :index="$loop->index" />
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


        </div>
    </div>
</x-app-layout>
