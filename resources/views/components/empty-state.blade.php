@props(['icon' => 'check-circle', 'title', 'description' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center']) }}>
    <div class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
        <x-icon :name="$icon" class="size-6" />
    </div>
    <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
