<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Set up')]
class Setup extends Component
{
    public string $app_name = '';

    public string $company_name = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $setup_token = '';

    public function mount(): void
    {
        $this->app_name = settings('app_name');
    }

    public function save()
    {
        abort_if(User::exists(), 403, 'This application has already been set up.');

        $this->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'company_name' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'setup_token' => [config('app.setup_token') ? 'required' : 'nullable', 'string'],
        ]);

        if (config('app.setup_token') && ! hash_equals((string) config('app.setup_token'), $this->setup_token)) {
            $this->addError('setup_token', 'The setup token is incorrect.');

            return;
        }

        settings()->set([
            'app_name' => $this->app_name,
            'company_name' => $this->company_name,
            'mail_from_name' => $this->app_name,
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => Role::Admin,
        ]);

        Cache::forever('app.installed', true);
        Auth::login($user, remember: true);

        return $this->redirectRoute('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.setup');
    }
}
