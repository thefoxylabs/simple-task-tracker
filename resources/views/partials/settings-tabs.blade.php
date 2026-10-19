@php
    $tabs = [
        'branding' => ['Branding', 'paint', route('settings', ['tab' => 'branding'])],
        'general' => ['General', 'cog', route('settings', ['tab' => 'general'])],
        'mail' => ['Email (SMTP)', 'mail', route('settings', ['tab' => 'mail'])],
        'security' => ['Security', 'shield', route('settings', ['tab' => 'security'])],
        'status' => ['System status', 'pulse', route('settings.status')],
    ];
@endphp
<nav class="segmented mb-6 sm:inline-flex" aria-label="Settings sections"
     x-data x-init="const a = $el.querySelector('[aria-current]'); if (a) $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2">
    @foreach ($tabs as $key => [$label, $icon, $url])
        <a href="{{ $url }}" wire:navigate class="{{ $current === $key ? 'tab tab-active' : 'tab' }} flex items-center gap-1.5" @if ($current === $key) aria-current="page" @endif>
            <x-icon :name="$icon" class="size-4" /> {{ $label }}
        </a>
    @endforeach
</nav>
