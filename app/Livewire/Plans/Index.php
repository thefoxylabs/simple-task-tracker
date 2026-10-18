<?php

namespace App\Livewire\Plans;

use App\Enums\Period;
use App\Models\Plan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Plans')]
class Index extends Component
{
    #[Url]
    public string $period = '';

    #[Url]
    public string $when = 'current';

    #[On('plan-saved')]
    public function refresh(): void
    {
        unset($this->plans);
    }

    #[Computed]
    public function plans()
    {
        $today = today()->toDateString();

        return Plan::query()
            ->with(['owner', 'group', 'project'])
            ->withProgress()
            ->when(Period::tryFrom($this->period), fn ($q, $period) => $q->where('period', $period))
            ->when($this->when === 'current', fn ($q) => $q->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today))
            ->when($this->when === 'upcoming', fn ($q) => $q->whereDate('starts_on', '>', $today))
            ->when($this->when === 'past', fn ($q) => $q->whereDate('ends_on', '<', $today))
            ->orderBy($this->when === 'past' ? 'ends_on' : 'starts_on', $this->when === 'past' ? 'desc' : 'asc')
            ->get()
            ->groupBy(fn (Plan $plan) => $plan->period->value);
    }

    public function render()
    {
        return view('livewire.plans.index', ['periods' => Period::cases()]);
    }
}
