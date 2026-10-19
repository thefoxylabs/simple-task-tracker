<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Support\SetupToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password as PasswordRule;
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
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'setup_token' => [SetupToken::required() ? 'required' : 'nullable', 'string'],
        ]);

        $key = 'setup|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('setup_token', 'Too many attempts. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes.');

            return;
        }

        if (! SetupToken::matches($this->setup_token)) {
            RateLimiter::hit($key, 600);
            $this->addError('setup_token', 'The setup token is incorrect.');

            return;
        }

        // Only one request may create the first admin, even if two arrive at once.
        $user = Cache::lock('app.setup', 10)->block(5, function () {
            if (User::exists()) {
                return null;
            }

            settings()->set([
                'app_name' => $this->app_name,
                'company_name' => $this->company_name,
                'mail_from_name' => $this->app_name,
            ]);

            return User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'role' => Role::Admin,
            ]);
        });

        abort_unless($user, 403, 'This application has already been set up.');

        RateLimiter::clear($key);
        SetupToken::forget();
        Cache::forever('app.installed', true);

        Auth::login($user);
        session()->regenerate();

        return $this->redirectRoute('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.setup');
    }
}
