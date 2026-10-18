<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum Period: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** The noun used in views such as "This week". */
    public function noun(): string
    {
        return match ($this) {
            self::Daily => 'day',
            self::Weekly => 'week',
            self::Monthly => 'month',
            self::Quarterly => 'quarter',
            self::Yearly => 'year',
        };
    }

    /**
     * The start and end of the period containing the given date.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(CarbonInterface $date): array
    {
        $date = CarbonImmutable::instance($date);

        return match ($this) {
            self::Daily => [$date->startOfDay(), $date->endOfDay()],
            self::Weekly => [$date->startOfWeek(CarbonInterface::MONDAY), $date->endOfWeek(CarbonInterface::SUNDAY)],
            self::Monthly => [$date->startOfMonth(), $date->endOfMonth()],
            self::Quarterly => [$date->startOfQuarter(), $date->endOfQuarter()],
            self::Yearly => [$date->startOfYear(), $date->endOfYear()],
        };
    }

    /** Move a date forward (or backward) by a number of periods. */
    public function shift(CarbonInterface $date, int $times = 1): CarbonImmutable
    {
        $date = CarbonImmutable::instance($date);

        return match ($this) {
            self::Daily => $date->addDays($times),
            self::Weekly => $date->addWeeks($times),
            self::Monthly => $date->addMonthsNoOverflow($times),
            self::Quarterly => $date->addMonthsNoOverflow(3 * $times),
            self::Yearly => $date->addYearsNoOverflow($times),
        };
    }

    /** A human readable name for the period containing the given date. */
    public function describe(CarbonInterface $date): string
    {
        [$start, $end] = $this->range($date);

        return match ($this) {
            self::Daily => $start->format('l, j M Y'),
            self::Weekly => $start->format('j M').' – '.$end->format('j M Y'),
            self::Monthly => $start->format('F Y'),
            self::Quarterly => 'Q'.$start->quarter.' '.$start->year,
            self::Yearly => (string) $start->year,
        };
    }
}
