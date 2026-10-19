<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Sign in')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Limit guesses per account (5 per 5 minutes) and per IP address
        // (20 per minute, to stop one machine trying many accounts).
        $accountKey = 'login|'.Str::lower($this->email);
        $ipKey = 'login-ip|'.request()->ip();

        foreach ([$accountKey => 5, $ipKey => 20] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw ValidationException::withMessages([
                    'email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
                ]);
            }
        }

        $credentials = ['email' => $this->email, 'password' => $this->password, 'is_active' => true];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($accountKey, 300);
            RateLimiter::hit($ipKey, 60);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($accountKey);
        session()->regenerate();

        return $this->redirectIntended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
