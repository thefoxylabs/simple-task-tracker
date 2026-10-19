<?php

namespace App\Livewire;

use App\Support\SystemStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Settings → System status: is everything the app depends on working?
 */
#[Title('System status')]
class SystemStatusPage extends Component
{
    public bool $live = false;

    public ?string $checkedAt = null;

    public function boot(): void
    {
        Gate::authorize('admin');
    }

    /** Runs right after the page appears, so the slower live checks don't delay it. */
    public function runLiveChecks(): void
    {
        $this->live = true;
        $this->checkedAt = now()->toIso8601String();
        Cache::forget('system.problem_count');
    }

    public function render(SystemStatus $status)
    {
        $groups = $status->checks($this->live);
        $all = collect($groups)->flatten(1);

        return view('livewire.system-status', [
            'groups' => $groups,
            'errors' => $all->where('status', 'error')->count(),
            'warnings' => $all->where('status', 'warning')->count(),
            'passing' => $all->where('status', 'ok')->count(),
            'total' => $all->whereNotIn('status', ['info', 'pending'])->count(),
        ]);
    }
}
