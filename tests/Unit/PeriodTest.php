<?php

namespace Tests\Unit;

use App\Enums\Period;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase
{
    public function test_ranges_cover_the_whole_period(): void
    {
        $date = CarbonImmutable::parse('2026-10-15 13:45');

        $this->assertSame(['2026-10-15', '2026-10-15'], $this->dates(Period::Daily->range($date)));
        $this->assertSame(['2026-10-12', '2026-10-18'], $this->dates(Period::Weekly->range($date)));
        $this->assertSame(['2026-10-01', '2026-10-31'], $this->dates(Period::Monthly->range($date)));
        $this->assertSame(['2026-10-01', '2026-12-31'], $this->dates(Period::Quarterly->range($date)));
        $this->assertSame(['2026-01-01', '2026-12-31'], $this->dates(Period::Yearly->range($date)));
    }

    public function test_shifting_does_not_overflow_months(): void
    {
        $date = CarbonImmutable::parse('2026-01-31');

        $this->assertSame('2026-02-28', Period::Monthly->shift($date)->toDateString());
        $this->assertSame('2026-04-30', Period::Quarterly->shift($date)->toDateString());
        $this->assertSame('2025-10-31', Period::Quarterly->shift($date, -1)->toDateString());
    }

    public function test_describes_periods(): void
    {
        $date = CarbonImmutable::parse('2026-10-15');

        $this->assertSame('Q4 2026', Period::Quarterly->describe($date));
        $this->assertSame('October 2026', Period::Monthly->describe($date));
        $this->assertSame('2026', Period::Yearly->describe($date));
    }

    private function dates(array $range): array
    {
        return array_map(fn ($d) => $d->toDateString(), $range);
    }
}
