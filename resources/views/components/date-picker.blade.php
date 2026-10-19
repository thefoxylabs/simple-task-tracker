{{--
    Custom date / date-time / time picker.

    <x-date-picker wire:model="due_on" />
    <x-date-picker wire:model.live="due_at" mode="datetime" />
    <x-date-picker wire:model="due_time" mode="time" />
--}}
@props(['mode' => 'date', 'placeholder' => null, 'defaultTime' => '17:00', 'id' => null, 'clearable' => true])
@php
    $placeholder ??= match ($mode) {
        'time' => 'Select a time',
        'datetime' => 'Select date & time',
        default => 'Select a date',
    };
    $panelWidth = match ($mode) {
        'datetime' => 'sm:w-[33rem]',
        'time' => 'sm:w-64',
        default => 'sm:w-[19.5rem]',
    };
@endphp
<div x-data="datePicker(@js(['mode' => $mode, 'defaultTime' => $defaultTime, 'placeholder' => $placeholder]))"
     x-modelable="value"
     wire:ignore
     x-on:keydown.escape="if (open) { $event.stopPropagation(); close() }"
     x-on:click.outside="close(false)"
     x-on:resize.window="position()"
     {{ $attributes->merge(['class' => 'relative @container']) }}>

    {{-- Trigger --}}
    <button type="button" x-ref="trigger" @if ($id) id="{{ $id }}" @endif
            x-on:click="toggle()"
            :aria-expanded="open"
            aria-haspopup="dialog"
            :class="open ? 'ring-2 ring-brand-600' : ''"
            class="input flex items-center gap-2.5 pr-9 text-left">
        <x-icon :name="$mode === 'time' ? 'clock' : 'calendar'" class="size-4 text-slate-400" />
        <span class="min-w-0 flex-1 truncate" :class="label ? 'text-slate-900' : 'text-slate-400'" x-text="label || placeholder">{{ $placeholder }}</span>
        <span x-cloak x-show="relative" x-text="relative" class="hidden shrink-0 rounded-md bg-brand-50 px-1.5 py-0.5 text-[0.7rem] font-semibold text-brand-700 @[22rem]:inline"></span>
    </button>

    @if ($clearable)
        <button type="button" x-cloak x-show="value" x-on:click="clear()" title="Clear"
                class="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
            <x-icon name="x" class="size-3.5" />
            <span class="sr-only">Clear</span>
        </button>
    @endif

    {{-- Mobile backdrop --}}
    <div x-cloak x-show="open && mobile" x-transition.opacity x-on:click="close()" class="fixed inset-0 z-[79] bg-slate-900/40 backdrop-blur-[1px]"></div>

    {{-- Panel --}}
    <div x-cloak x-show="open" x-ref="panel" tabindex="-1" role="dialog" aria-label="Choose {{ $mode === 'time' ? 'a time' : 'a date' }}"
         x-on:keydown="onKeydown($event)"
         :style="style"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-1 sm:scale-[0.98]"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         :class="mobile ? 'inset-x-0 bottom-0 max-h-[88vh] overflow-y-auto rounded-t-3xl pb-[env(safe-area-inset-bottom)]' : 'rounded-2xl'"
         class="fixed z-[80] w-full {{ $panelWidth }} bg-white shadow-2xl ring-1 ring-slate-900/10 outline-none">

        {{-- Mobile sheet header --}}
        <div x-show="mobile" class="sticky top-0 z-10 rounded-t-3xl bg-white/95 px-5 pt-3 pb-2 backdrop-blur">
            <div class="mx-auto mb-3 h-1.5 w-10 rounded-full bg-slate-200"></div>
            <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ $mode === 'time' ? 'Time' : 'Selected' }}</p>
            <p class="text-lg font-semibold text-slate-900" x-text="label || placeholder"></p>
        </div>

        <div class="flex flex-col sm:flex-row">
            {{-- Calendar --}}
            <template x-if="hasDate">
                <div class="p-4 sm:w-[19.5rem] sm:shrink-0">
                    {{-- Shortcuts --}}
                    <div class="mb-4 grid grid-cols-4 gap-1.5">
                        <button type="button" x-on:click="shortcut(0)" class="rounded-full bg-slate-100 px-1 py-1 text-[0.7rem] font-medium whitespace-nowrap text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Today</button>
                        <button type="button" x-on:click="shortcut(1)" class="rounded-full bg-slate-100 px-1 py-1 text-[0.7rem] font-medium whitespace-nowrap text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Tomorrow</button>
                        <button type="button" x-on:click="nextWeekday(1)" class="rounded-full bg-slate-100 px-1 py-1 text-[0.7rem] font-medium whitespace-nowrap text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Next Mon</button>
                        <button type="button" x-on:click="shortcut(7)" class="rounded-full bg-slate-100 px-1 py-1 text-[0.7rem] font-medium whitespace-nowrap text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">In a week</button>
                    </div>

                    {{-- Header --}}
                    <div class="mb-3 flex items-center justify-between">
                        <button type="button" x-on:click="zoomOut()" :disabled="view === 'years'"
                                class="flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 disabled:hover:bg-transparent">
                            <span x-text="title"></span>
                            <x-icon name="chevron-down" class="size-3.5 text-slate-400" x-show="view !== 'years'" />
                        </button>
                        <div class="flex items-center">
                            <button type="button" x-on:click="previous()" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Previous">
                                <x-icon name="chevron-left" class="size-4" />
                            </button>
                            <button type="button" x-on:click="next()" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Next">
                                <x-icon name="chevron-right" class="size-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Days --}}
                    <div x-show="view === 'days'">
                        <div class="mb-1 grid grid-cols-7">
                            <template x-for="weekday in weekdays" :key="weekday">
                                <span class="py-1 text-center text-[0.7rem] font-semibold tracking-wide text-slate-400 uppercase" x-text="weekday.slice(0, 2)"></span>
                            </template>
                        </div>
                        <div class="grid grid-cols-7 gap-y-1" role="grid">
                            <template x-for="d in days" :key="d.key">
                                <button type="button" x-on:click="select(d.key)" :aria-label="d.label" :aria-selected="d.selected"
                                        :class="{
                                            'bg-brand-600 text-white font-semibold shadow-md shadow-brand-600/30 hover:bg-brand-700': d.selected,
                                            'bg-brand-50 text-brand-700 font-semibold': d.today && ! d.selected,
                                            'text-slate-300 hover:bg-slate-50': d.outside && ! d.selected && ! d.today,
                                            'text-slate-700 hover:bg-slate-100': ! d.outside && ! d.selected && ! d.today,
                                            'ring-2 ring-brand-500/60': d.key === focusedDay && ! d.selected,
                                        }"
                                        class="relative mx-auto flex size-10 items-center justify-center rounded-full text-sm tabular-nums transition sm:size-9">
                                    <span x-text="d.day"></span>
                                    <span x-show="d.today && ! d.selected" class="absolute bottom-1 size-1 rounded-full bg-brand-600"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Months --}}
                    <div x-show="view === 'months'" class="grid grid-cols-3 gap-2">
                        <template x-for="(name, index) in months" :key="name">
                            <button type="button" x-on:click="pickMonth(index)"
                                    :class="index === month ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'text-slate-700 hover:bg-slate-100'"
                                    class="rounded-xl py-3 text-sm font-medium transition" x-text="name.slice(0, 3)"></button>
                        </template>
                    </div>

                    {{-- Years --}}
                    <div x-show="view === 'years'" class="grid grid-cols-3 gap-2">
                        <template x-for="y in years" :key="y">
                            <button type="button" x-on:click="pickYear(y)"
                                    :class="y === year ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : (y === new Date().getFullYear() ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-100')"
                                    class="rounded-xl py-3 text-sm font-medium tabular-nums transition" x-text="y"></button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Time --}}
            <template x-if="hasTime">
                <div class="flex flex-1 flex-col border-slate-100 p-4" :class="hasDate ? 'border-t sm:border-t-0 sm:border-l' : ''">
                    <div class="mb-3 flex items-baseline justify-between">
                        <p class="text-sm font-semibold text-slate-900">Time</p>
                        <p class="text-2xl font-semibold tracking-tight text-slate-900 tabular-nums">
                            <span x-text="hour12"></span>:<span x-text="String(minute).padStart(2, '0')"></span>
                            <span class="text-sm font-medium text-slate-500" x-text="meridiem"></span>
                        </p>
                    </div>

                    <div class="grid flex-1 grid-cols-[1fr_1fr_auto] gap-2">
                        <div data-time-column class="relative h-44 snap-y overflow-y-auto overscroll-contain rounded-xl bg-slate-50 p-1 sm:h-52 [scrollbar-width:thin]">
                            <template x-for="h in hours" :key="'h' + h">
                                <button type="button" x-on:click="setHour(h)" :aria-selected="h === hour12"
                                        :class="h === hour12 ? 'bg-brand-600 text-white font-semibold shadow-sm' : 'text-slate-700 hover:bg-white'"
                                        class="block w-full snap-center rounded-lg py-2 text-center text-sm tabular-nums transition" x-text="h"></button>
                            </template>
                        </div>
                        <div data-time-column class="relative h-44 snap-y overflow-y-auto overscroll-contain rounded-xl bg-slate-50 p-1 sm:h-52 [scrollbar-width:thin]">
                            <template x-for="m in minutes" :key="'m' + m">
                                <button type="button" x-on:click="setMinute(m)" :aria-selected="m === minute"
                                        :class="m === minute ? 'bg-brand-600 text-white font-semibold shadow-sm' : 'text-slate-700 hover:bg-white'"
                                        class="block w-full snap-center rounded-lg py-2 text-center text-sm tabular-nums transition" x-text="String(m).padStart(2, '0')"></button>
                            </template>
                        </div>
                        <div class="flex flex-col gap-1 self-start rounded-xl bg-slate-50 p-1">
                            <template x-for="period in ['AM', 'PM']" :key="period">
                                <button type="button" x-on:click="setMeridiem(period)"
                                        :class="period === meridiem ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                                        class="rounded-lg px-3 py-2 text-xs font-semibold transition" x-text="period"></button>
                            </template>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <template x-for="preset in presets" :key="preset.label">
                            <button type="button" x-on:click="setTime(preset.h, preset.m)"
                                    :class="preset.h === hour && preset.m === minute ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-600/30' : 'bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700'"
                                    class="rounded-full px-2.5 py-1 text-xs font-medium transition" x-text="preset.label"></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-4 py-3">
            <button type="button" x-on:click="clear()" x-show="value" class="text-sm font-medium text-slate-500 transition hover:text-red-600">Clear</button>
            <span x-show="! value" class="text-xs text-slate-400">{{ $mode === 'time' ? 'Pick an hour and minute' : 'Use ← → ↑ ↓ and Enter' }}</span>
            <button type="button" x-on:click="close()" class="btn btn-primary px-4 py-1.5">Done</button>
        </div>
    </div>
</div>
