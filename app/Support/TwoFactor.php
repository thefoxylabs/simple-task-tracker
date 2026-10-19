<?php

namespace App\Support;

use App\Enums\TwoFactorMethod;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

/**
 * Two-factor sign-in: email codes (the default) or an authenticator app.
 */
class TwoFactor
{
    public const CODE_MINUTES = 10;

    public const TRUST_DAYS = 30;

    public const TRUST_COOKIE = 'two_factor_trusted';

    // Session keys
    public const PENDING = 'two_factor.pending';

    public const PASSED = 'two_factor.passed';

    public function __construct(protected Google2FA $google2fa) {}

    // ----- Email codes ---------------------------------------------------------------

    /**
     * Email a fresh 6-digit code. Returns false if one was sent very recently
     * or the email couldn't be sent.
     */
    public function sendEmailCode(User $user): bool
    {
        $key = 'two-factor-send|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return false;
        }

        RateLimiter::hit($key, 30);

        $code = (string) random_int(100000, 999999);

        Cache::put($this->emailCodeKey($user), [
            'hash' => $this->hash($code),
            'expires' => now()->addMinutes(self::CODE_MINUTES)->timestamp,
        ], now()->addMinutes(self::CODE_MINUTES));

        try {
            // Sent straight away, not queued: the person is waiting for it.
            $user->notifyNow(new TwoFactorCode($code));

            return true;
        } catch (Throwable $e) {
            report($e);
            SystemStatus::recordMailFailure($e);

            return false;
        }
    }

    public function secondsUntilResend(User $user): int
    {
        return RateLimiter::availableIn('two-factor-send|'.$user->id);
    }

    // ----- Authenticator apps -------------------------------------------------------

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl(settings('app_name'), $user->email, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd));

        // Drop the XML declaration so the SVG can be placed inline in the page.
        return trim(preg_replace('/^<\?xml.*?\?>/s', '', $writer->writeString($url)));
    }

    public function verifyAppCode(string $secret, string $code, ?User $user = null): bool
    {
        $code = preg_replace('/\D/', '', $code);

        if (strlen($code) !== 6) {
            return false;
        }

        // Passing the last used time step (or 0) makes the library return the
        // matched time step, and rejects codes at or before the last one used.
        $lastUsed = $user ? (int) Cache::get($this->appTimestampKey($user), 0) : 0;
        $timestamp = $this->google2fa->verifyKeyNewer($secret, $code, $lastUsed, 1);

        if ($timestamp === false) {
            return false;
        }

        // Each code can only be used once.
        if ($user) {
            Cache::put($this->appTimestampKey($user), $timestamp, now()->addMinutes(5));
        }

        return true;
    }

    /** @return list<string> */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    // ----- Verifying a sign-in --------------------------------------------------------

    public function verify(User $user, string $code): bool
    {
        $code = trim($code);

        if ($user->twoFactorMethod() === TwoFactorMethod::App) {
            return $this->verifyAppCode($user->two_factor_secret, $code, $user);
        }

        $stored = Cache::get($this->emailCodeKey($user));
        $code = preg_replace('/\D/', '', $code);

        if (! $stored || $stored['expires'] < now()->timestamp || ! hash_equals($stored['hash'], $this->hash($code))) {
            return false;
        }

        Cache::forget($this->emailCodeKey($user));

        return true;
    }

    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals($stored, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    // ----- Trusted devices ------------------------------------------------------------

    public function isTrustedDevice(Request $request, User $user): bool
    {
        $value = (string) $request->cookie(self::TRUST_COOKIE);

        return settings('two_factor_trusted_devices')
            && $user->two_factor_trust_token
            && hash_equals($this->trustValue($user), $value);
    }

    public function trustDevice(User $user): void
    {
        if (! $user->two_factor_trust_token) {
            $user->forceFill(['two_factor_trust_token' => Str::random(64)])->save();
        }

        Cookie::queue(Cookie::make(self::TRUST_COOKIE, $this->trustValue($user), self::TRUST_DAYS * 24 * 60));
    }

    /** Forget every trusted device, e.g. after 2FA is reset or changed. */
    public function forgetTrustedDevices(User $user): void
    {
        $user->forceFill(['two_factor_trust_token' => null])->save();
    }

    /** Remove the user's own 2FA set-up (they fall back to email codes if 2FA is required). */
    public function reset(User $user): void
    {
        $user->forceFill([
            'two_factor_method' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_trust_token' => null,
        ])->save();
    }

    // ----- Helpers --------------------------------------------------------------------

    protected function trustValue(User $user): string
    {
        return $user->id.'|'.hash_hmac('sha256', $user->id.'|'.$user->two_factor_trust_token, (string) config('app.key'));
    }

    protected function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    protected function emailCodeKey(User $user): string
    {
        return 'two-factor-email-code|'.$user->id;
    }

    protected function appTimestampKey(User $user): string
    {
        return 'two-factor-app-timestamp|'.$user->id;
    }
}
