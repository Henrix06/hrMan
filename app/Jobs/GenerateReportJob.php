<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(
        public readonly string $reportType,
        public readonly array $params = [],
        public readonly ?int $requestedBy = null,
    ) {}

    public function handle(): void
    {
        try {
            Log::info('GenerateReportJob started.', [
                'report_type' => $this->reportType,
                'params' => $this->params,
                'requested_by' => $this->requestedBy,
            ]);

            // Report generation logic goes here.
            // Dispatch a notification to $this->requestedBy when done.

            Log::info('GenerateReportJob completed.', ['report_type' => $this->reportType]);
        } catch (\Throwable $e) {
            Log::error('GenerateReportJob failed.', [
                'report_type' => $this->reportType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
