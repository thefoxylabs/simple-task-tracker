<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Higher number = more important, used for sorting. */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-50 text-slate-600 ring-slate-500/20',
            self::Medium => 'bg-brand-50 text-brand-700 ring-brand-600/20',
            self::High => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Urgent => 'bg-red-50 text-red-700 ring-red-600/20',
        };
    }

    public function barClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-300',
            self::Medium => 'bg-brand-500',
            self::High => 'bg-amber-400',
            self::Urgent => 'bg-red-500',
        };
    }
}
