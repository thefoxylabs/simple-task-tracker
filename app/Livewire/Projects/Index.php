<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Projects')]
class Index extends Component
{
    #[Url]
    public bool $archived = false;

    #[Url(as: 'q')]
    public string $search = '';

    #[On('project-saved')]
    public function refresh(): void
    {
        unset($this->projects);
    }

    #[Computed]
    public function projects()
    {
        $user = auth()->user();

        return Project::query()
            ->when($this->archived, fn ($q) => $q->archived(), fn ($q) => $q->active())
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->visibleTo($user)
            ->with('lead')
            ->withProgress()
            ->orderByDesc($this->archived ? 'archived_at' : 'created_at')
            ->get();
    }

    public function toggleArchive(int $id): void
    {
        Gate::authorize('manage');

        $project = Project::findOrFail($id);
        $project->update(['archived_at' => $project->isArchived() ? null : now()]);

        unset($this->projects);
        $this->dispatch('notify', message: $project->isArchived() ? 'Project archived' : 'Project restored');
    }

    public function delete(int $id): void
    {
        Gate::authorize('admin');

        Project::findOrFail($id)->delete();

        unset($this->projects);
        $this->dispatch('notify', message: 'Project deleted');
    }

    public function render()
    {
        return view('livewire.projects.index');
    }
}
