<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Staff => 'Staff',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Admin => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            self::Manager => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Staff => 'bg-slate-50 text-slate-600 ring-slate-500/20',
        };
    }
}
