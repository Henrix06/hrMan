<?php

namespace App\Actions\Assets;

use App\Models\AssetAssignment;
use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReturnAsset
{
    public function execute(
        AssetAssignment $assignment,
        User $receivedBy,
        string $returnDate,
        string $conditionOnReturn,
        ?string $returnNotes = null,
    ): AssetAssignment {
        if ($assignment->status !== 'active') {
            throw new RuntimeException('Only active assignments can be returned.');
        }

        // Verify record has not been tampered with before processing return
        if ($assignment->integrity_hash && !$assignment->verifyIntegrity()) {
            AuditLog::record(
                'security_alert',
                "Integrity check FAILED for asset assignment #{$assignment->id} — possible tampering detected.",
                $assignment,
            );
            throw new RuntimeException('Assignment record integrity check failed. Please contact an administrator.');
        }

        return DB::transaction(function () use ($assignment, $receivedBy, $returnDate, $conditionOnReturn, $returnNotes) {
            $old = $assignment->toArray();

            $assignment->update([
                'status'               => 'returned',
                'returned_to'          => $receivedBy->id,
                'actual_return_date'   => $returnDate,
                'condition_on_return'  => $conditionOnReturn,
                'return_notes'         => $returnNotes,
            ]);

            $assignment->asset->update([
                'status'    => 'available',
                'condition' => $conditionOnReturn,
            ]);

            AuditLog::record(
                'update',
                "Returned asset [{$assignment->asset->asset_tag}] {$assignment->asset->name} from {$assignment->employee->name}",
                $assignment,
                $old,
                $assignment->fresh()->toArray(),
            );

            HrNotification::notify(
                $assignment->user_id,
                'asset_returned',
                'Asset Return Recorded',
                "Your return of {$assignment->asset->name} ({$assignment->asset->asset_tag}) has been recorded.",
                route('assets.my'),
            );

            return $assignment;
        });
    }
}
