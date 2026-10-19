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
<div {{ $attributes->merge(['class' => 'card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:gap-4 sm:p-5']) }}>
    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl sm:size-11 {{ $tones[$tone] }}">
        <x-icon :name="$icon" class="size-5 sm:size-6" />
    </div>
    <div class="min-w-0">
        <p class="text-xs text-slate-500 sm:text-sm">{{ $label }}</p>
        <p class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $value }}</p>
    </div>
</div>
