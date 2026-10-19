<?php

namespace App\Http\Middleware;

use App\Enums\TwoFactorMethod;
use App\Support\TwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-in people who need 2FA (for example because an admin just made it
 * mandatory) must verify before they can continue.
 */
class EnsureTwoFactorPassed
{
    public function __construct(protected TwoFactor $twoFactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->session()->get(TwoFactor::PASSED) || ! $user->hasTwoFactor()) {
            return $next($request);
        }

        if ($this->twoFactor->isTrustedDevice($request, $user)) {
            $request->session()->put(TwoFactor::PASSED, true);

            return $next($request);
        }

        if (! $request->session()->has(TwoFactor::PENDING)) {
            $request->session()->put(TwoFactor::PENDING, ['id' => $user->id, 'remember' => false, 'at' => now()->timestamp]);

            if ($user->twoFactorMethod() === TwoFactorMethod::Email) {
                $this->twoFactor->sendEmailCode($user);
            }
        }

        if ($request->hasHeader('X-Livewire')) {
            abort(403, 'Please verify your sign-in to continue.');
        }

        return redirect()->guest(route('two-factor.challenge'));
    }
}
