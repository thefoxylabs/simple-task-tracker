@props(['label', 'value', 'icon', 'tone' => 'brand'])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'sky' => 'bg-brand-50 text-brand-600',
        'red' => 'bg-red-50 text-red-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'card flex items-center gap-4 p-5']) }}>
    <div class="flex size-11 items-center justify-center rounded-xl {{ $tones[$tone] }}">
        <x-icon :name="$icon" class="size-6" />
    </div>
    <div>
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
    </div>
</div>
