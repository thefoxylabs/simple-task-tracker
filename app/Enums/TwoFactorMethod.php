<?php

namespace App\Enums;

enum TwoFactorMethod: string
{
    case Email = 'email';
    case App = 'app';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email codes',
            self::App => 'Authenticator app',
        };
    }
}
