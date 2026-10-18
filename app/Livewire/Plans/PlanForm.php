<?php

namespace App\Livewire\Plans;

use App\Enums\Period;
use App\Models\Plan;
use App\Models\Project;
use App\Models\StaffGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanForm extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $planId = null;

    public string $title = '';

    public string $description = '';

    public string $period = 'weekly';

    public string $date = '';

    public ?int $project_id = null;

    public ?int $staff_group_id = null;

    public ?int $user_id = null;

    #[On('create-plan')]
    public function create(?string $period = null): void
    {
        Gate::authorize('manage');

        $this->resetForm();
        $this->period = Period::tryFrom((string) $period)?->value ?? 'weekly';
        $this->date = today()->toDateString();
        $this->open = true;
    }

    #[On('edit-plan')]
    public function edit(int $id): void
    {
        Gate::authorize('manage');

        $plan = Plan::findOrFail($id);

        $this->resetForm();
        $this->planId = $plan->id;
        $this->fill($plan->only(['title', 'project_id', 'staff_group_id', 'user_id']));
        $this->description = (string) $plan->description;
        $this->period = $plan->period->value;
        $this->date = $plan->starts_on->toDateString();
        $this->open = true;
    }

    /** The date range the plan will cover, shown live in the form. */
    #[Computed]
    public function range(): ?string
    {
        $period = Period::tryFrom($this->period);

        if (! $period || ! strtotime($this->date)) {
            return null;
        }

        [$start, $end] = $period->range(Carbon::parse($this->date));

        return $period->describe($start).($period === Period::Daily ? '' : ' ('.$start->format('j M').' – '.$end->format('j M Y').')');
    }

    #[Computed]
    public function options(): array
    {
        return [
            'projects' => Project::active()->orWhere('id', $this->project_id)->orderBy('name')->get(['id', 'name']),
            'groups' => StaffGroup::orderBy('name')->get(['id', 'name']),
            'people' => User::active()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function save(): void
    {
        Gate::authorize('manage');

        $data = $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'period' => ['required', Rule::enum(Period::class)],
            'date' => ['required', 'date'],
            'project_id' => ['nullable', Rule::exists('projects', 'id')],
            'staff_group_id' => ['nullable', Rule::exists('staff_groups', 'id')],
            'user_id' => ['nullable', Rule::exists('users', 'id')],
        ]);

        [$start, $end] = Period::from($data['period'])->range(Carbon::parse($data['date']));

        $attributes = [
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'period' => $data['period'],
            'starts_on' => $start->toDateString(),
            'ends_on' => $end->toDateString(),
            'project_id' => $data['project_id'] ?: null,
            'staff_group_id' => $data['staff_group_id'] ?: null,
            'user_id' => $data['user_id'] ?: null,
        ];

        $plan = $this->planId
            ? tap(Plan::findOrFail($this->planId))->update($attributes)
            : Plan::create($attributes + ['created_by' => auth()->id()]);

        $this->dispatch('plan-saved', id: $plan->id);
        $this->dispatch('notify', message: $this->planId ? 'Plan updated' : 'Plan created');
        $this->close();
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['planId', 'title', 'description', 'period', 'date', 'project_id', 'staff_group_id', 'user_id']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.plans.plan-form');
    }
}
