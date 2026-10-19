@props(['values' => [], 'label', 'unit' => ''])
@php
    $chartValues = collect($values)->map(fn ($value) => max(0, (float) $value));
    $maximum = max(1, $chartValues->max() ?? 0);
    $points = $chartValues->values()->map(fn ($value, $index) => [30 + ($chartValues->count() > 1 ? $index * 570 / ($chartValues->count() - 1) : 285), 165 - ($value / $maximum * 130)]);
    $titleId = 'chart-'.\Illuminate\Support\Str::uuid();
@endphp
@if($chartValues->isNotEmpty())
<figure class="space-y-3">
    <svg viewBox="0 0 640 210" class="metric-chart block w-full" role="img" aria-labelledby="{{ $titleId }}">
        <title id="{{ $titleId }}">{{ $label }}. {{ $chartValues->map(fn ($value, $key) => $key.': '.number_format($value,1,',','.').' '.$unit)->join('; ') }}</title>
        <path d="M30 25 V165 H610" fill="none" stroke="currentColor" opacity="0.4" />
        <path d="M30 100 H610 M30 35 H610" fill="none" stroke="currentColor" opacity="0.1" />
        <polyline class="chart-line" pathLength="1" points="{{ $points->map(fn ($point) => implode(',', $point))->join(' ') }}" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round" />
        @if($chartValues->count() === 1)
            <rect x="280" y="{{ $points->first()[1] }}" width="70" height="{{ 165 - $points->first()[1] }}" rx="10" fill="currentColor" opacity="0.15" />
            <text x="315" y="{{ max(20, $points->first()[1] - 12) }}" text-anchor="middle" fill="currentColor" font-size="18" font-weight="700">{{ number_format($chartValues->first(), 0, ',', '.') }}</text>
        @endif
        @foreach($points as $point)<circle cx="{{ $point[0] }}" cy="{{ $point[1] }}" r="4" fill="currentColor" />@endforeach
        @if($chartValues->count() === 1)
        <text x="315" y="195" fill="currentColor" font-size="13" text-anchor="middle">{{ $chartValues->keys()->first() }}</text>
        @else
        <text x="30" y="195" fill="currentColor" font-size="13">{{ $chartValues->keys()->first() }}</text>
        <text x="610" y="195" fill="currentColor" font-size="13" text-anchor="end">{{ $chartValues->keys()->last() }}</text>
        @endif
    </svg>
    <figcaption class="text-sm text-slate-600">{{ $label }} · máximo {{ number_format($chartValues->max(),1,',','.') }} {{ $unit }}. Consulte o detalhamento por mês abaixo.</figcaption>
</figure>
@else
<p>Sem dados no período.</p>
@endif
