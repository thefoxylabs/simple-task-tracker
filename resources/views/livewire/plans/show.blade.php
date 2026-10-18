<div>
    <x-page-header :title="$plan->title" :description="$plan->description">
        <x-slot:before>
            <a href="{{ route('plans.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700"><x-icon name="arrow-left" class="size-4" /> Plans</a>
        </x-slot:before>
        <x-slot:actions>
            @can('manage')
                <button type="button" wire:click="$dispatch('edit-plan', { id: {{ $plan->id }} })" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</button>
                <button type="button" wire:click="delete" wire:confirm="Delete this plan? Its tasks will be kept." class="btn btn-secondary text-red-600"><x-icon name="trash" class="size-4" /> Delete</button>
            @endcan
            <button type="button" wire:click="$dispatch('create-task', { planId: {{ $plan->id }}, assigneeId: {{ $plan->user_id ?? 'null' }} })" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Add task</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-sm text-slate-500">{{ $plan->period->label() }} plan</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $plan->period->describe($plan->starts_on) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $plan->starts_on->format('j M') }} – {{ $plan->ends_on->format('j M Y') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">For</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $plan->audience() }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $plan->project ? 'Project: '.$plan->project->name : 'No project' }}</p>
        </div>
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">Progress</span>
                <span class="font-semibold text-slate-900">{{ $progress }}%</span>
            </div>
            <x-progress class="mt-3 h-2" :value="$progress" />
            <p class="mt-3 text-xs text-slate-500">{{ $done }} of {{ $this->tasks->count() }} tasks done{{ $overdue ? " · $overdue overdue" : '' }}</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Tasks in this plan</h2></div>
        @if ($this->tasks->isEmpty())
            <div class="p-6"><x-empty-state icon="list" title="No tasks yet" description="Add tasks to this plan to start tracking it." /></div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($this->tasks as $task)
                    <x-task-row :task="$task" wire:key="pt-{{ $task->id }}" />
                @endforeach
            </ul>
        @endif
    </div>

    <livewire:plans.plan-form />
</div>
