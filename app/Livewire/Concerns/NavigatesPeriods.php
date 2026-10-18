<?php

namespace App\Livewire\Concerns;

use App\Enums\Period;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Url;

/**
 * Day / week / month / quarter / year switcher with previous & next buttons.
 */
trait NavigatesPeriods
{
    #[Url]
    public string $period = 'weekly';

    #[Url]
    public string $date = '';

    public function periodEnum(): Period
    {
        return Period::tryFrom($this->period) ?? Period::Weekly;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function periodRange(): array
    {
        return $this->periodEnum()->range($this->anchor());
    }

    public function periodLabel(): string
    {
        return $this->periodEnum()->describe($this->anchor());
    }

    public function previousPeriod(): void
    {
        $this->date = $this->periodEnum()->shift($this->anchor(), -1)->toDateString();
    }

    public function nextPeriod(): void
    {
        $this->date = $this->periodEnum()->shift($this->anchor())->toDateString();
    }

    public function currentPeriod(): void
    {
        $this->date = '';
    }

    protected function anchor(): CarbonImmutable
    {
        try {
            return $this->date ? CarbonImmutable::instance(Carbon::parse($this->date)) : CarbonImmutable::today();
        } catch (\Throwable) {
            return CarbonImmutable::today();
        }
    }
}
