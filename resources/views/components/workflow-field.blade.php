@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => true])
<label class="block stack-1 text-sm font-medium text-slate-700">
    <span>{{ $label }}</span>
    @if ($type === 'textarea')
        <textarea name="{{ $name }}" {{ $attributes->class(['block w-full rounded-lg border-slate-300']) }} @required($required) rows="3">{{ old(trim(str_replace(['[', ']'], ['.', ''], $name), '.'), $value) }}</textarea>
    @else
        <input name="{{ $name }}" type="{{ $type }}" value="{{ old(trim(str_replace(['[', ']'], ['.', ''], $name), '.'), $value) }}" {{ $attributes->class(['block w-full rounded-lg border-slate-300']) }} @required($required)>
    @endif
</label>
