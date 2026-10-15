<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Está tudo certo com sua ideia?</h2><p class="mt-2 text-slate-600">Confira o que escreveu. Sua ideia só será enviada quando você confirmar abaixo.</p></x-slot>
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-8">
        <section class="apple-card space-y-5 p-6">
            <h3 class="text-2xl font-bold">{{ $data['title'] }}</h3>
            @foreach (['problem_description' => 'O que acontece hoje', 'solution_description' => 'Sua sugestão', 'expected_results' => 'Benefícios esperados', 'resources_needed' => 'O que seria necessário', 'additional_information' => 'Observações'] as $field => $label)
                @if (!empty($data[$field]))<div><h4 class="font-semibold">{{ $label }}</h4><p class="whitespace-pre-line text-slate-600">{{ $data[$field] }}</p></div>@endif
            @endforeach
            <p><strong>Setor do problema:</strong> {{ $originSector->name }}</p>
            <p><strong>Setores beneficiados:</strong> {{ $beneficiarySectors->pluck('name')->join(', ') }}</p>
        </section>
        <form method="POST" action="{{ route('ideas.store') }}" class="apple-card space-y-4 p-6" x-data="{ sending: false }" @submit="if (sending) { $event.preventDefault(); } else { sending = true; }">
            @csrf
            @foreach ($data as $field => $value)
                @if (is_array($value))
                    @foreach ($value as $item)<input type="hidden" name="{{ $field }}[]" value="{{ $item }}">@endforeach
                @else<input type="hidden" name="{{ $field }}" value="{{ $value }}">@endif
            @endforeach
            <p class="text-sm text-slate-600">Após o envio, a equipe vai analisar sua sugestão. Você poderá acompanhar o andamento em “Minhas ideias”. Seu nome não será exibido aos demais funcionários.</p>
            <div class="flex flex-wrap gap-3"><button name="_action" value="edit" class="apple-button--light" :aria-disabled="sending">Quero corrigir</button><button name="_action" value="send" class="apple-button" :aria-disabled="sending" x-text="sending ? 'Aguarde…' : 'Enviar minha ideia'">Enviar minha ideia</button></div>
        </form>
    </div>
</x-app-layout>
