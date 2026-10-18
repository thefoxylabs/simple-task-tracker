<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProjectForm extends Component
{
    use WithFileUploads;

    public const COLORS = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#64748b'];

    public bool $open = false;

    #[Locked]
    public ?int $projectId = null;

    public string $name = '';

    public string $description = '';

    public string $color = '#6366f1';

    public ?int $lead_id = null;

    public string $starts_on = '';

    public string $due_on = '';

    public array $uploads = [];

    #[On('create-project')]
    public function create(): void
    {
        Gate::authorize('manage');

        $this->resetForm();
        $this->color = self::COLORS[array_rand(self::COLORS)];
        $this->open = true;
    }

    #[On('edit-project')]
    public function edit(int $id): void
    {
        Gate::authorize('manage');

        $project = Project::findOrFail($id);

        $this->resetForm();
        $this->projectId = $project->id;
        $this->fill($project->only(['name', 'color', 'lead_id']));
        $this->description = (string) $project->description;
        $this->starts_on = $project->starts_on?->toDateString() ?? '';
        $this->due_on = $project->due_on?->toDateString() ?? '';
        $this->open = true;
    }

    #[Computed]
    public function project(): ?Project
    {
        return $this->projectId ? Project::with('attachments')->find($this->projectId) : null;
    }

    #[Computed]
    public function people()
    {
        return User::active()->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        Gate::authorize('manage');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:10000'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'lead_id' => ['nullable', Rule::exists('users', 'id')],
            'starts_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'uploads.*' => Uploads::RULES,
        ]);

        unset($data['uploads']);
        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);

        $project = $this->project
            ? tap($this->project)->update($data)
            : Project::create($data + ['created_by' => auth()->id()]);

        foreach ($this->uploads as $file) {
            $project->attach($file, auth()->user());
        }

        $this->open = false;
        $this->dispatch('project-saved', id: $project->id);
        $this->dispatch('notify', message: $this->projectId ? 'Project updated' : 'Project created');
        $this->resetForm();
    }

    public function deleteAttachment(int $id): void
    {
        Gate::authorize('manage');

        Attachment::whereMorphedTo('attachable', $this->project)->findOrFail($id)->delete();
        unset($this->project);
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['projectId', 'name', 'description', 'color', 'lead_id', 'starts_on', 'due_on', 'uploads']);
        $this->resetValidation();
        unset($this->project);
    }

    public function render()
    {
        return view('livewire.project-form');
    }
}
