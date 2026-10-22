<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Regras financeiras</h2></x-slot>
    <div class="mx-auto max-w-5xl stack-6 px-4 py-8">
        <p class="text-sm text-slate-600">Cadastre os critérios e as regras que serão escolhidos ao criar um ciclo de seleção de ideias.</p>
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach([
                ['route'=>'financial.technical.index', 'title'=>'Critérios técnicos', 'count'=>$criteriaCount, 'text'=>'Configure os critérios e as orientações para avaliar as ideias.'],
                ['route'=>'financial.policies.index', 'title'=>'Regras de bonificação', 'count'=>$policiesCount, 'text'=>'Configure o valor por ideia, a nota mínima e as regras adicionais.'],
            ] as $module)
                <a href="{{ route($module['route']) }}" class="apple-card block stack-3 p-6"><x-sidebar-icon name="settings" /><p class="text-3xl font-bold">{{ $module['count'] }}</p><h3 class="text-lg font-bold">{{ $module['title'] }}</h3><p class="text-sm text-slate-600">{{ $module['text'] }}</p></a>
            @endforeach
        </div>
        <p class="text-sm text-slate-500">Alterar um cadastro não modifica os ciclos já criados. Bonificações continuam dependendo de aprovação e divulgação; o pagamento é feito pela empresa fora do sistema.</p>
    </div>
</x-app-layout>
