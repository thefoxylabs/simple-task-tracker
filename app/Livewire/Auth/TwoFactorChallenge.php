<?php

namespace App\Livewire\Auth;

use App\Enums\TwoFactorMethod;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The second sign-in step: enter the emailed code, a code from an
 * authenticator app, or a recovery code.
 */
#[Layout('layouts::guest')]
#[Title('Verify it\'s you')]
class TwoFactorChallenge extends Component
{
    public string $code = '';

    public bool $recovery = false;

    public bool $trust = false;

    public string $notice = '';

    public function mount()
    {
        if (! $this->user) {
            return $this->redirectRoute('login', navigate: true);
        }
    }

    /** The person signing in: from the login step, or a signed-in user who must now verify. */
    #[Computed]
    public function user(): ?User
    {
        $pending = session(TwoFactor::PENDING);

        if (! $pending || $pending['at'] < now()->subMinutes(15)->timestamp) {
            return null;
        }

        return User::where('is_active', true)->find($pending['id']);
    }

    #[Computed]
    public function method(): TwoFactorMethod
    {
        return $this->user->twoFactorMethod() ?? TwoFactorMethod::Email;
    }

    public function verify(TwoFactor $twoFactor)
    {
        $user = $this->user;

        if (! $user) {
            return $this->backToLogin('Your sign-in expired. Please sign in again.');
        }

        $this->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Enter your code.']);

        $key = 'two-factor|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->backToLogin('Too many incorrect codes. Please sign in again.');
        }

        $valid = $this->recovery && $this->method === TwoFactorMethod::App
            ? $twoFactor->useRecoveryCode($user, $this->code)
            : $twoFactor->verify($user, $this->code);

        if (! $valid) {
            RateLimiter::hit($key, 600);
            $this->addError('code', $this->recovery ? 'That recovery code isn\'t valid.' : 'That code isn\'t right, or it has expired.');

            return;
        }

        RateLimiter::clear($key);
        $remember = (bool) session(TwoFactor::PENDING.'.remember');
        session()->forget(TwoFactor::PENDING);

        if (! Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user, $remember);
        }

        session()->regenerate();
        session()->put(TwoFactor::PASSED, true);

        if ($this->trust && settings('two_factor_trusted_devices')) {
            $twoFactor->trustDevice($user);
        }

        return $this->redirectIntended(route('dashboard'));
    }

    public function resend(TwoFactor $twoFactor): void
    {
        $user = $this->user;

        if (! $user || $this->method !== TwoFactorMethod::Email) {
            return;
        }

        $this->resetErrorBag();

        if ($twoFactor->sendEmailCode($user)) {
            $this->notice = 'We sent a new code to '.$user->maskedEmail().'.';
        } elseif ($seconds = $twoFactor->secondsUntilResend($user)) {
            $this->notice = 'Please wait '.$seconds.' seconds before asking for another code.';
        } else {
            $this->addError('code', 'We couldn\'t send the email. Please contact your administrator.');
        }
    }

    public function toggleRecovery(): void
    {
        $this->recovery = ! $this->recovery;
        $this->reset('code');
        $this->resetErrorBag();
    }

    public function cancel()
    {
        return $this->backToLogin();
    }

    protected function backToLogin(?string $message = null)
    {
        session()->forget(TwoFactor::PENDING);

        if (Auth::check()) {
            Auth::guard('web')->logout();
        }

        if ($message) {
            session()->flash('status', $message);
        }

        return $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge');
    }
}
