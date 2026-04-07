<?php

namespace Tests\Unit\Support\Leave;

use App\Support\Leave\BusinessDayCalculator;
use PHPUnit\Framework\TestCase;

class BusinessDayCalculatorTest extends TestCase
{
    public function test_it_counts_only_weekdays_in_an_inclusive_range(): void
    {
        $calculator = new BusinessDayCalculator();

        $days = $calculator->count('2026-03-23', '2026-03-29');

        $this->assertSame(5, $days);
    }

    public function test_it_returns_zero_when_end_date_is_before_start_date(): void
    {
        $calculator = new BusinessDayCalculator();

        $days = $calculator->count('2026-03-29', '2026-03-23');

        $this->assertSame(0, $days);
    }
}
