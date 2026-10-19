@props(['name'])
@php
    $paths = [
        'home'=>'M3 10 12 3l9 7v11h-6v-7H9v7H3z',
        'vote'=>'m9 3 8 5-5 8-8-5z M3 16v5h18v-5 M8 18h8',
        'idea'=>'M9 18h6 M9 21h6 M8 14a6 6 0 1 1 8 0c-1 1-1 2-1 3H9c0-1 0-2-1-3z',
        'bell'=>'M5 17h14l-2-3V9a5 5 0 0 0-10 0v5z M10 21h4',
        'check'=>'M8 4H4v17h16V4h-4 M9 3h6v4H9z m-1 11 3 3 5-6',
        'settings'=>'m9 3-1 3-3 1-2 3 2 2-1 4 3 2 3-1 3 2 3-2v-4l3-2-1-4-3-1-1-3z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6',
        'chart'=>'M4 3v18h17 M8 17v-5 M13 17V8 M18 17V4',
        'audit'=>'M5 3h10l4 4v14H5z M9 11h6 M9 15h6 M9 18h4',
        'user'=>'M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M4 21v-2a8 8 0 0 1 16 0v2',
        'logout'=>'M9 4H4v16h5 M13 7l5 5-5 5 M8 12h13',
    ];
@endphp
<svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['home'] }}" /></svg>
