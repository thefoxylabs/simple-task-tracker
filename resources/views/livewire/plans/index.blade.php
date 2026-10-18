<div>
    <x-page-header title="Plans" description="Daily, weekly, monthly, quarterly and yearly goals. Link tasks to a plan to track it.">
        <x-slot:actions>
            @can('manage')
                <button type="button" wire:click="$dispatch('create-plan', { period: '{{ $period }}' })" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New plan</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <div class="flex flex-wrap rounded-lg bg-slate-100 p-1">
            <button type="button" wire:click="$set('period', '')" class="{{ $period === '' ? 'tab tab-active' : 'tab' }}">All</button>
            @foreach ($periods as $p)
                <button type="button" wire:click="$set('period', '{{ $p->value }}')" class="{{ $period === $p->value ? 'tab tab-active' : 'tab' }}">{{ $p->label() }}</button>
            @endforeach
        </div>
        <div class="flex rounded-lg bg-slate-100 p-1">
            @foreach (['current' => 'Current', 'upcoming' => 'Upcoming', 'past' => 'Past'] as $key => $label)
                <button type="button" wire:click="$set('when', '{{ $key }}')" class="{{ $when === $key ? 'tab tab-active' : 'tab' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if ($this->plans->isEmpty())
        <x-empty-state icon="target" title="No plans here" description="Create a plan for a day, week, month, quarter or year, then link tasks to it.">
            @can('manage')
                <button type="button" wire:click="$dispatch('create-plan', { period: '{{ $period }}' })" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New plan</button>
            @endcan
        </x-empty-state>
    @else
        <div class="space-y-8">
            @foreach ($periods as $p)
                @continue(! $this->plans->has($p->value))
                <section wire:key="period-{{ $p->value }}">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-slate-500 uppercase">{{ $p->label() }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->plans[$p->value] as $plan)
                            <a href="{{ route('plans.show', $plan) }}" wire:navigate wire:key="plan-{{ $plan->id }}" class="card group flex flex-col p-5 transition hover:shadow-md">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="badge bg-brand-50 text-brand-700 ring-brand-600/20">{{ $plan->period->describe($plan->starts_on) }}</span>
                                    @if ($plan->isCurrent())
                                        <span class="text-xs font-medium text-emerald-600">● Now</span>
                                    @endif
                                </div>
                                <h3 class="mt-3 font-semibold text-slate-900 group-hover:text-brand-700">{{ $plan->title }}</h3>
                                <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                    <x-icon name="{{ $plan->owner ? 'user' : 'users' }}" class="size-3.5" /> {{ $plan->audience() }}
                                    @if ($plan->project) · {{ $plan->project->name }} @endif
                                </p>
                                <div class="mt-auto pt-5">
                                    <div class="mb-2 flex items-center justify-between text-xs">
                                        <span class="font-medium text-slate-700">{{ $plan->progress() }}%</span>
                                        <span class="text-slate-500">{{ $plan->done_tasks_count }}/{{ $plan->tasks_count }} tasks</span>
                                    </div>
                                    <x-progress :value="$plan->progress()" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <livewire:plans.plan-form />
</div>
