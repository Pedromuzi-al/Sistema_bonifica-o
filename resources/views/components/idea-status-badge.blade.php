@props(['status'])

@php
    [$label, $color] = match ($status) {
        'submitted' => ['Enviada', 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20'],
        'triage' => ['Em triagem', 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-400/10 dark:text-violet-300 dark:ring-violet-400/20'],
        'evaluation' => ['Em avaliação', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20'],
        'approved' => ['Aprovada', 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20'],
        'rejected' => ['Rejeitada', 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20'],
        'implementation' => ['Em implementação', 'bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-400/10 dark:text-cyan-300 dark:ring-cyan-400/20'],
        'implemented' => ['Implementada', 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-400/10 dark:text-teal-300 dark:ring-teal-400/20'],
        'completed' => ['Concluída', 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700/70 dark:text-slate-200 dark:ring-slate-600'],
        default => [\App\Models\Idea::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)), 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700/70 dark:text-slate-200 dark:ring-slate-600'],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset', $color]) }}>
    {{ $label }}
</span>
