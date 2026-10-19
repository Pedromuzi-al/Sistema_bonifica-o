@props(['label', 'value', 'icon' => 'chart', 'tone' => 'sky', 'description' => null, 'index' => 0])
<article class="stat-card" data-tone="{{ $tone }}" style="--stat-delay: {{ min(10, max(0, (int) $index)) * 55 }}ms" aria-label="{{ $label }}: {{ $value }}">
    <div class="stat-card-heading"><span class="stat-card-icon"><x-sidebar-icon :name="$icon" /></span><p class="stat-card-label">{{ $label }}</p></div>
    <p class="stat-card-value">{{ $value }}</p>
    @if($description)<p class="stat-card-description">{{ $description }}</p>@endif
</article>
