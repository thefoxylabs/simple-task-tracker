<?php

namespace App\Livewire;

use App\Models\StaffGroup;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Staff groups')]
class StaffGroups extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $color = '#6366f1';

    public function boot(): void
    {
        Gate::authorize('manage');
    }

    #[Computed]
    public function groups()
    {
        return StaffGroup::withCount(['members' => fn ($q) => $q->active()])
            ->with(['members' => fn ($q) => $q->active()->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'description', 'color']);
        $this->color = ProjectForm::COLORS[array_rand(ProjectForm::COLORS)];
        $this->resetValidation();
        $this->open = true;
    }

    public function edit(int $id): void
    {
        $group = StaffGroup::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $group->id;
        $this->name = $group->name;
        $this->description = (string) $group->description;
        $this->color = $group->color;
        $this->open = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $this->editingId
            ? StaffGroup::findOrFail($this->editingId)->update($data)
            : StaffGroup::create($data);

        $this->dispatch('notify', message: $this->editingId ? 'Group updated' : 'Group created');
        $this->open = false;
        unset($this->groups);
    }

    public function delete(int $id): void
    {
        StaffGroup::findOrFail($id)->delete();
        unset($this->groups);
        $this->dispatch('notify', message: 'Group deleted. Its members were kept.');
    }

    public function render()
    {
        return view('livewire.staff-groups');
    }
}
