<?php

namespace App\Livewire\Auth;

use App\Enums\TwoFactorMethod;
use App\Support\TwoFactor;
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

        // Check the password without signing in yet: 2FA may be needed first.
        if (! Auth::validate($credentials)) {
            RateLimiter::hit($accountKey, 300);
            RateLimiter::hit($ipKey, 60);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($accountKey);

        $user = Auth::getLastAttempted();
        $twoFactor = app(TwoFactor::class);

        if ($user->hasTwoFactor() && ! $twoFactor->isTrustedDevice(request(), $user)) {
            session()->regenerate();
            session()->put(TwoFactor::PENDING, ['id' => $user->id, 'remember' => $this->remember, 'at' => now()->timestamp]);

            if ($user->twoFactorMethod() === TwoFactorMethod::Email) {
                $twoFactor->sendEmailCode($user);
            }

            return $this->redirectRoute('two-factor.challenge', navigate: true);
        }

        Auth::login($user, $this->remember);
        session()->regenerate();
        session()->put(TwoFactor::PASSED, true);

        return $this->redirectIntended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
