<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts::guest')]
#[Title('Forgot password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function send(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        // Stop one machine from sending lots of reset emails.
        $key = 'forgot-password|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many requests. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes.');

            return;
        }

        RateLimiter::hit($key, 600);

        try {
            Password::sendResetLink(['email' => $this->email, 'is_active' => true]);
        } catch (Throwable $e) {
            report($e);
        }

        // Always show the same message so accounts can't be discovered.
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
