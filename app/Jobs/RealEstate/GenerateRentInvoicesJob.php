<?php

namespace App\Jobs\RealEstate;

use App\Services\RealEstate\RentInvoicingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRentInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Carbon $billingDate) {}

    public function handle(RentInvoicingService $service): void
    {
        try {
            $invoices = $service->generateDueInvoices($this->billingDate);
            Log::info('Rent invoices generated.', ['count' => $invoices->count(), 'date' => $this->billingDate->toDateString()]);
        } catch (\Throwable $e) {
            Log::error('GenerateRentInvoicesJob failed.', ['error' => $e->getMessage(), 'date' => $this->billingDate->toDateString()]);
            throw $e;
        }
    }
}
