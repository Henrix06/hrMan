<?php

namespace App\Jobs\RealEstate;

use App\Services\RealEstate\PenaltyService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyLatePenaltiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PenaltyService $service): void
    {
        try {
            $service->applyPendingPenalties(Carbon::today());
            Log::info('Late penalties applied.', ['date' => Carbon::today()->toDateString()]);
        } catch (\Throwable $e) {
            Log::error('ApplyLatePenaltiesJob failed.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
