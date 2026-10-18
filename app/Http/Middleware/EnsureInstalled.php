<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends visitors to the setup wizard until the first admin account exists.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = Cache::get('app.installed') ?: (User::exists() && Cache::forever('app.installed', true));

        if (! $installed && ! $request->routeIs('setup')) {
            return redirect()->route('setup');
        }

        if ($installed && $request->routeIs('setup')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
