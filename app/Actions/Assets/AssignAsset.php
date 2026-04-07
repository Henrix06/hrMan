<?php

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssignAsset
{
    public function execute(
        Asset $asset,
        User $employee,
        User $assignedBy,
        string $assignedDate,
        ?string $expectedReturnDate = null,
        ?string $notes = null,
    ): AssetAssignment {
        if (!$asset->isAvailable()) {
            throw new RuntimeException("Asset [{$asset->asset_tag}] is not available for assignment.");
        }

        return DB::transaction(function () use ($asset, $employee, $assignedBy, $assignedDate, $expectedReturnDate, $notes) {
            $asset->update(['status' => 'assigned']);

            $assignment = AssetAssignment::create([
                'asset_id'             => $asset->id,
                'user_id'              => $employee->id,
                'assigned_by'          => $assignedBy->id,
                'assigned_date'        => $assignedDate,
                'expected_return_date' => $expectedReturnDate,
                'status'               => 'active',
                'notes'                => $notes,
            ]);

            // Seal the record with an integrity hash after creation (so created_at is set)
            $assignment->refresh();
            $assignment->update(['integrity_hash' => $assignment->computeIntegrityHash()]);

            AuditLog::record(
                'create',
                "Assigned asset [{$asset->asset_tag}] {$asset->name} to {$employee->name}",
                $assignment,
                [],
                $assignment->toArray(),
            );

            HrNotification::notify(
                $employee->id,
                'asset_assigned',
                'Asset Assigned to You',
                "{$assignedBy->name} assigned {$asset->name} ({$asset->asset_tag}) to you.",
                route('assets.my'),
            );

            return $assignment;
        });
    }
}
