<?php

namespace App\Services\RealEstate;

use App\Models\HrNotification;
use App\Models\RealEstate\Lease;
use App\Models\RealEstate\MaintenanceRequest;
use App\Models\User;

class MaintenanceService
{
    public function create(array $data, User $actor): MaintenanceRequest
    {
        if (isset($data['lease_id']) && $data['lease_id'] !== null) {
            $lease = Lease::findOrFail($data['lease_id']);

            if ($lease->status !== 'active') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lease_id' => 'Maintenance requests can only be submitted for active leases.',
                ]);
            }
        }

        $request = MaintenanceRequest::create(array_merge($data, [
            'reference'  => MaintenanceRequest::generateReference('MNT'),
            'status'     => 'open',
            'created_by' => $actor->id,
        ]));

        User::permission('real_estate.manage_maintenance')->get()->each(function (User $manager) use ($request) {
            HrNotification::notify(
                $manager->id,
                'maintenance_request',
                'New Maintenance Request',
                "A new maintenance request has been submitted: {$request->title}",
            );
        });

        return $request;
    }

    public function assign(MaintenanceRequest $request, User $assignee, User $actor): MaintenanceRequest
    {
        if ($request->status !== 'open') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Only open requests can be assigned.',
            ]);
        }

        $request->status      = 'in_progress';
        $request->assigned_to = $assignee->id;
        $request->assigned_at = now();
        $request->save();

        return $request;
    }

    public function resolve(MaintenanceRequest $request, string $notes, ?float $cost, User $actor): MaintenanceRequest
    {
        if ($request->status !== 'in_progress') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Only in-progress requests can be resolved.',
            ]);
        }

        $request->status           = 'resolved';
        $request->resolved_at      = now();
        $request->resolution_notes = $notes;
        $request->repair_cost      = $cost;
        $request->save();

        if ($request->re_tenant_id) {
            $portalUser = $request->reTenant?->portalUser;

            if ($portalUser) {
                HrNotification::notify(
                    $portalUser->id,
                    'maintenance_resolved',
                    'Maintenance Request Resolved',
                    "Your maintenance request has been resolved: {$request->title}",
                );
            }
        }

        return $request;
    }

    public function close(MaintenanceRequest $request, User $actor): MaintenanceRequest
    {
        if ($request->status !== 'resolved') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Only resolved requests can be closed.',
            ]);
        }

        $request->status = 'closed';
        $request->save();

        return $request;
    }

    public function cancel(MaintenanceRequest $request, User $actor): MaintenanceRequest
    {
        if (in_array($request->status, ['closed', 'resolved'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Closed or resolved requests cannot be cancelled.',
            ]);
        }

        $request->status = 'cancelled';
        $request->save();

        return $request;
    }
}
