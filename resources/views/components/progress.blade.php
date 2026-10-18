@props(['value', 'color' => null])
<div {{ $attributes->merge(['class' => 'h-1.5 w-full overflow-hidden rounded-full bg-slate-100']) }}>
    <div class="h-full rounded-full {{ $color ? '' : 'bg-brand-600' }}" style="width: {{ $value }}%; {{ $color ? "background-color: $color" : '' }}"></div>
</div>
