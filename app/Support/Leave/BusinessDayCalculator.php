<?php

namespace App\Support\Leave;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class BusinessDayCalculator
{
    public function count(CarbonInterface|string $startDate, CarbonInterface|string $endDate): int
    {
        $start = $startDate instanceof CarbonInterface ? $startDate->copy() : Carbon::parse($startDate);
        $end = $endDate instanceof CarbonInterface ? $endDate->copy() : Carbon::parse($endDate);

        if ($end->lt($start)) {
            return 0;
        }

        $totalDays = 0;
        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            if (!in_array($currentDate->dayOfWeek, [0, 6], true)) {
                $totalDays++;
            }

            $currentDate->addDay();
        }

        return $totalDays;
    }
}
