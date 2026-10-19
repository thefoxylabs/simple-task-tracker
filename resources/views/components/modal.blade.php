@props(['title', 'width' => 'max-w-lg', 'close' => null])
<div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
     x-data x-on:keydown.escape.window="$event.target.closest('.select2-container') || $refs.closeButton?.click()">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px]" @if($close) wire:click="{{ $close }}" @endif></div>
    <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center sm:p-6">
        <div {{ $attributes->merge(['class' => "relative w-full $width rounded-2xl bg-white shadow-xl ring-1 ring-slate-200"]) }}>
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                @if ($close)
                    <button type="button" x-ref="closeButton" wire:click="{{ $close }}" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x" />
                        <span class="sr-only">Close</span>
                    </button>
                @endif
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
