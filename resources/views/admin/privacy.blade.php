<x-app-layout>
<x-slot name="header"><h2 class="text-2xl font-bold">Solicitações de privacidade</h2></x-slot>
<div class="mx-auto max-w-5xl stack-6 px-4 py-8">
<p class="apple-card p-6">Analise a finalidade e os prazos de retenção aprovados pela empresa antes de decidir. A anonimização da conta remove os dados de acesso; documentos, textos das propostas e registros de negócio exigem revisão específica. Não confirme a revisão antes de tratar eventuais dados pessoais nesses registros e justificar o que precisa ser preservado.</p>
@forelse($requests as $item)
<section class="apple-card stack-4 p-6"><h3 class="text-xl font-bold">Solicitação #{{ $item->id }} — {{ $item->name }}</h3><p>{{ $item->email }}</p><p class="whitespace-pre-line">{{ $item->reason }}</p><p>Situação: {{ ['pending'=>'Pendente','retained'=>'Retenção justificada','account_anonymized'=>'Conta anonimizada'][$item->status] ?? $item->status }}</p>
@if($item->status === 'pending')<form method="POST" action="{{ route('admin.privacy.decision', $item->id) }}" class="stack-3" x-data @submit="if(!confirm('Registrar esta decisão de privacidade? A anonimização da conta é irreversível.')) $event.preventDefault()">@csrf<label class="block">Decisão<select name="action" class="block w-full rounded-lg"><option value="retain">Preservar dados com justificativa</option><option value="anonymize">Anonimizar dados da conta e encerrar acesso</option></select></label><x-workflow-field name="decision" label="Justificativa, dados preservados, prazo e comunicação ao solicitante" type="textarea" /><label class="block"><input type="checkbox" name="review_confirmed" value="1" required> Revisei documentos, textos e registros de auditoria, tratei dados pessoais desnecessários e documentei a retenção e a resposta ao solicitante.</label><button class="apple-button">Registrar decisão</button></form>@else<p>{{ $item->decision }}</p>@endif</section>
@empty<p>Nenhuma solicitação registrada.</p>@endforelse
{{ $requests->links() }}
</div>
</x-app-layout>
