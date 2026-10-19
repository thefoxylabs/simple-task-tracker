<?php

namespace App\Livewire;

use App\Enums\TwoFactorMethod;
use App\Support\TwoFactor;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Two-factor authentication" on the profile page.
 */
class TwoFactorSettings extends Component
{
    /** Actions that need the current password, and what runs once it's confirmed. */
    protected const PROTECTED_ACTIONS = ['enableEmail', 'startAppSetup', 'showRecoveryCodes', 'regenerateRecoveryCodes', 'switchToEmail', 'disable'];

    #[Locked]
    public string $pendingAction = '';

    public string $password = '';

    #[Locked]
    public bool $settingUpApp = false;

    public string $code = '';

    #[Locked]
    public array $recoveryCodes = [];

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function method(): ?TwoFactorMethod
    {
        return $this->user->twoFactorMethod();
    }

    #[Computed]
    public function required(): bool
    {
        return (bool) settings('two_factor_required');
    }

    #[Computed]
    public function qrCode(): ?string
    {
        $secret = session('two_factor.setup_secret');

        return $this->settingUpApp && $secret ? app(TwoFactor::class)->qrCodeSvg($this->user, $secret) : null;
    }

    #[Computed]
    public function setupKey(): ?string
    {
        $secret = session('two_factor.setup_secret');

        return $secret ? trim(chunk_split($secret, 4, ' ')) : null;
    }

    /** Ask for the password before running a sensitive action. */
    public function ask(string $action): void
    {
        abort_unless(in_array($action, self::PROTECTED_ACTIONS, true), 404);

        $this->resetErrorBag();
        $this->reset('password', 'recoveryCodes');
        $this->pendingAction = $action;
    }

    public function confirmPassword(TwoFactor $twoFactor): void
    {
        abort_unless(in_array($this->pendingAction, self::PROTECTED_ACTIONS, true), 404);

        $this->validate(['password' => ['required', 'current_password']], [
            'password.current_password' => 'That password isn\'t right.',
        ]);

        $action = $this->pendingAction;
        $this->reset('password', 'pendingAction');

        $this->{$action}($twoFactor);
    }

    public function cancel(): void
    {
        $this->reset('password', 'pendingAction', 'settingUpApp', 'code', 'recoveryCodes');
        session()->forget('two_factor.setup_secret');
        $this->resetErrorBag();
    }

    protected function enableEmail(TwoFactor $twoFactor): void
    {
        $twoFactor->reset($this->user);
        $this->user->forceFill(['two_factor_method' => TwoFactorMethod::Email])->save();

        $this->done('Email codes are on. You\'ll get a code by email when you sign in.');
    }

    protected function startAppSetup(TwoFactor $twoFactor): void
    {
        session()->put('two_factor.setup_secret', $twoFactor->generateSecret());
        $this->settingUpApp = true;
    }

    public function confirmApp(TwoFactor $twoFactor): void
    {
        $secret = session('two_factor.setup_secret');
        abort_unless($this->settingUpApp && $secret, 403);

        $this->validate(['code' => ['required', 'string']], ['code.required' => 'Enter the 6-digit code from your app.']);

        if (! $twoFactor->verifyAppCode($secret, $this->code)) {
            $this->addError('code', 'That code isn\'t right. Check the time on your phone is correct and try the next code.');

            return;
        }

        $codes = $twoFactor->generateRecoveryCodes();

        $this->user->forceFill([
            'two_factor_method' => TwoFactorMethod::App,
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
            'two_factor_trust_token' => null,
        ])->save();

        session()->forget('two_factor.setup_secret');
        $this->reset('settingUpApp', 'code');
        $this->recoveryCodes = $codes;
        $this->done('Your authenticator app is set up.');
    }

    protected function showRecoveryCodes(): void
    {
        $this->recoveryCodes = $this->user->two_factor_recovery_codes ?? [];
    }

    protected function regenerateRecoveryCodes(TwoFactor $twoFactor): void
    {
        $codes = $twoFactor->generateRecoveryCodes();
        $this->user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $this->recoveryCodes = $codes;
        $this->done('New recovery codes created. The old ones no longer work.');
    }

    protected function switchToEmail(TwoFactor $twoFactor): void
    {
        $this->enableEmail($twoFactor);
    }

    protected function disable(TwoFactor $twoFactor): void
    {
        abort_if($this->required, 403, 'Two-factor authentication is required by your organisation.');

        $twoFactor->reset($this->user);
        $this->done('Two-factor authentication is off.');
    }

    public function hideRecoveryCodes(): void
    {
        $this->reset('recoveryCodes');
    }

    protected function done(string $message): void
    {
        $this->user->refresh();
        unset($this->user, $this->method);
        session()->put(TwoFactor::PASSED, true);
        $this->dispatch('notify', message: $message);
    }

    public function render()
    {
        return view('livewire.two-factor-settings');
    }
}
