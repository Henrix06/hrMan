<?php

namespace App\Actions\Accounts;

use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveProcurement
{
    public function __construct(private readonly DebitAccount $debitAccount) {}

    public function approve(ProcurementRequest $request, User $reviewer, float $approvedAmount, ?string $notes = null): void
    {
        if (!$request->isPending()) {
            throw new RuntimeException('Only pending requests can be approved.');
        }

        if (!$request->account_id) {
            throw new RuntimeException('No account linked to this procurement request.');
        }

        DB::transaction(function () use ($request, $reviewer, $approvedAmount, $notes) {
            $old = $request->toArray();

            $request->update([
                'status'          => 'approved',
                'approved_amount' => $approvedAmount,
                'reviewed_by'     => $reviewer->id,
                'reviewed_at'     => now(),
                'review_notes'    => $notes,
            ]);

            $this->debitAccount->execute(
                $request->account,
                $approvedAmount,
                'procurement',
                "Procurement: {$request->reference} — {$request->title}",
                $request,
            );

            AuditLog::record('update', "Approved procurement {$request->reference} for {$approvedAmount}", $request, $old, $request->fresh()->toArray());

            HrNotification::notify(
                $request->requested_by,
                'procurement_approved',
                'Procurement Request Approved',
                "{$reviewer->name} approved your request "{$request->title}" for " . number_format($approvedAmount, 2) . ".",
                route('accounts.procurement'),
            );
        });
    }

    public function reject(ProcurementRequest $request, User $reviewer, ?string $notes = null): void
    {
        if (!$request->isPending()) {
            throw new RuntimeException('Only pending requests can be rejected.');
        }

        $old = $request->toArray();
        $request->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewer->id,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);

        AuditLog::record('update', "Rejected procurement {$request->reference}", $request, $old, $request->fresh()->toArray());

        HrNotification::notify(
            $request->requested_by,
            'procurement_rejected',
            'Procurement Request Rejected',
            "{$reviewer->name} rejected your request "{$request->title}"." . ($notes ? " Reason: {$notes}" : ''),
            route('accounts.procurement'),
        );
    }
}
