<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
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
