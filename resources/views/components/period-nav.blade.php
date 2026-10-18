{{-- Used inside components with the NavigatesPeriods trait. --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <div class="flex flex-wrap rounded-lg bg-slate-100 p-1">
        @foreach (\App\Enums\Period::cases() as $p)
            <button type="button" wire:click="$set('period', '{{ $p->value }}')" class="{{ $this->period === $p->value ? 'tab tab-active' : 'tab' }}">{{ $p->label() }}</button>
        @endforeach
    </div>
    <div class="flex items-center gap-1 rounded-lg bg-white p-1 shadow-sm ring-1 ring-slate-200">
        <button type="button" wire:click="previousPeriod" class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100"><x-icon name="chevron-left" class="size-4" /></button>
        <span class="min-w-[9rem] px-2 text-center text-sm font-medium text-slate-800">{{ $this->periodLabel() }}</span>
        <button type="button" wire:click="nextPeriod" class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100"><x-icon name="chevron-right" class="size-4" /></button>
    </div>
    @if ($this->date)
        <button type="button" wire:click="currentPeriod" class="btn btn-ghost">Today</button>
    @endif
</div>
