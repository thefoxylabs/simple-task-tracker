<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Your profile')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $job_title = '';

    public string $phone = '';

    public string $current_password = '';

    public string $email_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->job_title = (string) $user->job_title;
        $this->phone = (string) $user->phone;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $emailChanged = strcasecmp($this->email, $user->email) !== 0;

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            // Changing the sign-in email needs the current password.
            'email_password' => [$emailChanged ? 'required' : 'nullable', $emailChanged ? 'current_password' : 'nullable'],
        ], [
            'email_password.required' => 'Enter your current password to change your email address.',
        ]);

        unset($data['email_password']);
        $user->update(array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->reset('email_password');

        $this->dispatch('notify', message: 'Profile saved');
    }

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update(['password' => $this->password]);

        // Sign out everywhere else, so an old session can't keep using the account.
        Auth::logoutOtherDevices($this->password);
        session()->put('password_hash_web', auth()->user()->getAuthPassword());

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('notify', message: 'Password updated');
    }

    public function render()
    {
        return view('livewire.profile');
    }
}
