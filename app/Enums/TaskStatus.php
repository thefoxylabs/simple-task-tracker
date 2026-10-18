<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To do',
            self::InProgress => 'In progress',
            self::Review => 'In review',
            self::Done => 'Done',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::Todo => 'bg-slate-400',
            self::InProgress => 'bg-sky-500',
            self::Review => 'bg-violet-500',
            self::Done => 'bg-emerald-500',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Todo => 'bg-slate-50 text-slate-600 ring-slate-500/20',
            self::InProgress => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            self::Review => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            self::Done => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        };
    }
}
