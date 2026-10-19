<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Protects the first-run setup wizard, so that only someone with access to
 * the server can create the first admin account.
 *
 * Uses SETUP_TOKEN from .env when set. In production, if it isn't set, a
 * random token is generated and stored on the server; read it with
 * `php artisan app:setup-token`.
 */
class SetupToken
{
    public static function required(): bool
    {
        return filled(config('app.setup_token')) || app()->environment('production');
    }

    public static function fromEnvironment(): bool
    {
        return filled(config('app.setup_token'));
    }

    public static function value(): ?string
    {
        if (self::fromEnvironment()) {
            return (string) config('app.setup_token');
        }

        if (! app()->environment('production')) {
            return null;
        }

        $path = self::path();

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, Str::random(40));
            @chmod($path, 0600);
        }

        return trim(File::get($path));
    }

    public static function matches(string $input): bool
    {
        $token = self::value();

        return $token === null || hash_equals($token, $input);
    }

    /** The generated token is single-use: remove it once setup is done. */
    public static function forget(): void
    {
        File::delete(self::path());
    }

    protected static function path(): string
    {
        return storage_path('app/private/setup-token');
    }
}
