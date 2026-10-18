@props(['dark' => false])
<span class="flex items-center gap-2.5">
    @if ($logo = settings()->logoUrl())
        <img src="{{ $logo }}" alt="{{ settings('app_name') }}" class="h-8 w-auto max-w-[9rem] object-contain">
    @else
        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(settings('app_name'), 0, 1)) }}
        </span>
    @endif
    <span class="truncate text-base font-semibold {{ $dark ? 'text-white' : 'text-slate-900' }}">{{ settings('app_name') }}</span>
</span>
