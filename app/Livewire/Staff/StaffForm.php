<?php

namespace App\Livewire\Staff;

use App\Enums\Role;
use App\Models\StaffGroup;
use App\Models\User;
use App\Notifications\StaffInvitation;
use App\Support\Mailer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class StaffForm extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'staff';

    public ?int $staff_group_id = null;

    public string $job_title = '';

    public string $phone = '';

    public string $password = '';

    public bool $is_active = true;

    public bool $invite = true;

    #[On('create-staff')]
    public function create(): void
    {
        Gate::authorize('manage-user');

        $this->resetForm();
        $this->open = true;
    }

    #[On('edit-staff')]
    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('manage-user', $user);

        $this->resetForm();
        $this->userId = $user->id;
        $this->fill($user->only(['name', 'email', 'staff_group_id', 'is_active']));
        $this->role = $user->role->value;
        $this->job_title = (string) $user->job_title;
        $this->phone = (string) $user->phone;
        $this->invite = false;
        $this->open = true;
    }

    #[Computed]
    public function groups()
    {
        return StaffGroup::orderBy('name')->get(['id', 'name']);
    }

    /** Managers can only create staff; admins can create any role. */
    #[Computed]
    public function roles(): array
    {
        return auth()->user()->isAdmin() ? Role::cases() : [Role::Staff];
    }

    public function save(): void
    {
        $user = $this->userId ? User::findOrFail($this->userId) : null;
        Gate::authorize('manage-user', $user);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->userId)],
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, $this->roles))],
            'staff_group_id' => ['nullable', Rule::exists('staff_groups', 'id')],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => [$user || $this->invite ? 'nullable' : 'required', PasswordRule::defaults()],
            'is_active' => ['boolean'],
        ], [
            'password.required' => 'Set a password or send an invitation email.',
        ]);

        if ($user && $user->is(auth()->user()) && ($data['role'] !== $user->role->value || ! $data['is_active'])) {
            $this->addError('role', 'You can\'t change your own role or deactivate yourself.');

            return;
        }

        if ($user?->isAdmin() && ($data['role'] !== Role::Admin->value || ! $data['is_active'])
            && User::where('role', Role::Admin)->where('is_active', true)->count() <= 1) {
            $this->addError('role', 'There must be at least one active admin.');

            return;
        }

        $attributes = collect($data)->except('password')->map(fn ($v) => $v === '' ? null : $v)->all();

        if (filled($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        if ($user) {
            $user->update($attributes);
        } else {
            $user = User::create($attributes + ['password' => $attributes['password'] ?? Str::password(32)]);

            if ($this->invite) {
                Mailer::notify($user, new StaffInvitation(Password::createToken($user), auth()->user()->name));
            }
        }

        $this->dispatch('staff-saved');
        $this->dispatch('notify', message: $this->userId ? 'Staff member updated' : ($this->invite ? 'Staff member added and invited' : 'Staff member added'));
        $this->close();
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['userId', 'name', 'email', 'role', 'staff_group_id', 'job_title', 'phone', 'password', 'is_active', 'invite']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.staff.staff-form');
    }
}
