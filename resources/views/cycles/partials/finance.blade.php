<section class="apple-card stack-4 p-6" aria-labelledby="cycle-finance-title">
    <div><h3 id="cycle-finance-title" class="text-xl font-bold">Bonificações do ciclo</h3><p class="mt-2 text-sm text-slate-600">{{ $canManage ? 'Valores em reais. O total aprovado inclui o que já foi divulgado; não some as duas colunas.' : 'Somente bonificações já divulgadas são apresentadas.' }} O pagamento é realizado pela empresa fora do sistema.</p></div>
    <div class="grid gap-4 sm:grid-cols-3">
        @if($canManage)
        <div class="result-money"><p>Pendente de aprovação</p><strong>R$ {{ number_format($cycleReport['pending_amount'],2,',','.') }}</strong></div>
        <div class="result-money"><p>Total aprovado</p><strong>R$ {{ number_format($cycleReport['approved_amount'],2,',','.') }}</strong></div>
        @endif
        <div class="result-money"><p>Divulgado</p><strong>R$ {{ number_format($cycleReport['published_amount'],2,',','.') }}</strong></div>
    </div>
    @if($canManage && ($reportMode ?? false))<a class="apple-button--light" href="{{ route('cycles.show',$cycle) }}#cycle-awards">Gerenciar decisões e bonificações</a>@endif
</section>
