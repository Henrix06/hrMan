<?php

use App\Jobs\RealEstate\ApplyLatePenaltiesJob;
use App\Jobs\RealEstate\GenerateRentInvoicesJob;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate rent invoices on the 1st of every month at 08:00
Schedule::job(new GenerateRentInvoicesJob(Carbon::today()))
    ->monthlyOn(1, '08:00')
    ->withoutOverlapping()
    ->onOneServer();

// Apply late payment penalties daily at 09:00
Schedule::job(new ApplyLatePenaltiesJob())
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();
