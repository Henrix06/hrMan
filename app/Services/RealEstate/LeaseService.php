<?php

namespace App\Services\RealEstate;

use App\Exceptions\RealEstate\LeaseActivationException;
use App\Exceptions\RealEstate\LeaseCreationException;
use App\Models\RealEstate\Lease;
use App\Models\RealEstate\Property;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaseService
{
    public function create(array $data, User $actor): Lease
    {
        return DB::transaction(function () use ($data, $actor) {
            $property = Property::findOrFail($data['property_id']);

            if (!in_array($property->status, ['available', 'partially_let'])) {
                throw new LeaseCreationException('Property is not available for leasing.');
            }

            $tenant = \App\Models\RealEstate\RealEstateTenant::findOrFail($data['re_tenant_id']);

            if ($tenant->status === 'blacklisted') {
                throw new LeaseCreationException('Tenant is blacklisted.');
            }

            if ($tenant->status === 'inactive') {
                throw new LeaseCreationException('Tenant is inactive.');
            }

            return Lease::create(array_merge($data, [
                'reference'  => Lease::generateReference('LSE'),
                'status'     => 'draft',
                'created_by' => $actor->id,
            ]));
        });
    }

    public function activate(Lease $lease, User $actor): Lease
    {
        return DB::transaction(function () use ($lease) {
            if ($lease->status !== 'draft') {
                throw new LeaseActivationException('Lease is not in draft status.');
            }

            if ($lease->deposit_amount > 0 && !$lease->deposit_paid) {
                throw new LeaseActivationException('Deposit has not been paid.');
            }

            $lease->status = 'active';
            $lease->save();

            $this->updatePropertyStatus($lease->property);

            return $lease;
        });
    }

    public function renew(Lease $lease, array $renewalData, User $actor): Lease
    {
        return DB::transaction(function () use ($lease, $renewalData, $actor) {
            $lease->status = 'renewed';
            $lease->save();

            return Lease::create(array_merge($renewalData, [
                'property_id'  => $lease->property_id,
                're_tenant_id' => $lease->re_tenant_id,
                'reference'    => Lease::generateReference('LSE'),
                'status'       => 'active',
                'created_by'   => $actor->id,
            ]));
        });
    }

    public function terminate(Lease $lease, string $reason, User $actor): Lease
    {
        return DB::transaction(function () use ($lease, $reason) {
            $lease->status             = 'terminated';
            $lease->terminated_at      = today();
            $lease->termination_reason = $reason;
            $lease->save();

            $this->updatePropertyStatus($lease->property);

            return $lease;
        });
    }

    public function getExpiringSoon(int $days = 30): Collection
    {
        return Lease::expiringSoon($days)->with(['property', 'reTenant'])->get();
    }

    private function updatePropertyStatus(Property $property): void
    {
        $activeCount = $property->leases()->where('status', 'active')->count();

        if ($activeCount === 0) {
            $property->status = 'available';
        } elseif ($activeCount >= $property->units) {
            $property->status = 'fully_let';
        } else {
            $property->status = 'partially_let';
        }

        $property->save();
    }
}
