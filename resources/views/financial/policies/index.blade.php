<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Regras de bonificação</h2></x-slot>
    <div class="mx-auto max-w-7xl stack-5 px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3"><a href="{{ route('financial.index') }}" class="text-sky-700 underline">← Regras financeiras</a><a href="{{ route('financial.policies.create') }}" class="apple-button">Nova regra de bonificação</a></div>
        <form method="GET" class="apple-card flex flex-wrap items-end gap-4 p-5"><x-workflow-field name="search" label="Buscar regra" :required="false" :value="request('search')" /><label>Situação<select name="status" class="mt-1 block rounded-lg"><option value="">Todas</option><option value="active" @selected(request('status')==='active')>Ativas</option><option value="inactive" @selected(request('status')==='inactive')>Inativas</option></select></label><button class="apple-button">Pesquisar</button><a href="{{ route('financial.policies.index') }}" class="text-sky-700 underline">Limpar</a></form>
        <section class="apple-card overflow-hidden"><div class="overflow-x-auto" role="region" aria-label="Regras de bonificação cadastradas" tabindex="0">
            <table class="cycle-list w-full text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50"><tr><th scope="col" class="p-4">Regra</th><th scope="col" class="p-4">Valor por ideia</th><th scope="col" class="p-4">Nota mínima</th><th scope="col" class="p-4">Situação</th><th scope="col" class="p-4">Ações</th></tr></thead><tbody class="divide-y divide-slate-200">
                @forelse($policies as $policy)<tr><th scope="row" class="p-4">{{ $policy->name }}<p class="mt-1 font-normal text-slate-500">{{ Str::limit($policy->description, 140) }}</p></th><td class="whitespace-nowrap p-4">R$ {{ number_format((float) $policy->reward_amount, 2, ',', '.') }}</td><td class="p-4">{{ number_format((float) $policy->minimum_score, 2, ',', '.') }}</td><td class="p-4"><span class="apple-pill cycle-status">{{ $policy->active ? 'Ativa' : 'Inativa' }}</span></td><td class="p-4"><a class="text-sky-700 underline" href="{{ route('financial.policies.edit', $policy) }}" aria-label="Editar regra {{ $policy->name }}">Editar</a></td></tr>
                @empty<tr><td colspan="5" class="p-6 text-center">Nenhuma regra encontrada. Use Nova regra de bonificação para cadastrar.</td></tr>@endforelse
            </tbody></table>
        </div></section>
        {{ $policies->links() }}
    </div>
</x-app-layout>
