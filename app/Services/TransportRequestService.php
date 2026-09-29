<?php

namespace App\Services;

use App\Exceptions\Sales\SalesException;
use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\SalesOrder;
use App\Models\TransportRequest;
use App\Models\User;

/**
 * Moving what has been sold: requesting transport for a confirmed order, then
 * the approval → dispatch → delivery journey that gets it to the customer.
 */
class TransportRequestService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function requestFromOrder(SalesOrder $order, User $requester, array $data): TransportRequest
    {
        if ($order->sold_by !== $requester->id && ! $requester->can('manage sales')) {
            throw SalesException::notYourOrder();
        }

        if (! in_array($order->status, ['confirmed', 'fulfilled'], true)) {
            throw SalesException::transportNeedsConfirmedOrder($order->status);
        }

        if ($order->hasOpenTransportRequest()) {
            throw SalesException::duplicateTransportRequest();
        }

        $request = TransportRequest::create([
            'reference' => TransportRequest::generateReference(),
            'sales_order_id' => $order->id,
            'requested_by' => $requester->id,
            'pickup_address' => $data['pickup_address'],
            'delivery_address' => $data['delivery_address'],
            'pickup_contact' => $data['pickup_contact'] ?: null,
            'delivery_contact' => $data['delivery_contact'] ?: null,
            'items_summary' => ($data['items_summary'] ?? null) ?: $order->itemsSummary(),
            'scheduled_date' => $data['scheduled_date'] ?: null,
            'notes' => $data['notes'] ?: null,
            'status' => 'pending',
            'tenant_id' => $order->tenant_id,
        ]);

        AuditLog::record('create', "Requested transport {$request->reference} for order {$order->reference}", $request);

        $this->notifyManagers($request);

        return $request;
    }

    /**
     * Approve a request and put a driver and vehicle behind it.
     *
     * @param  array<string, mixed>  $data
     */
    public function approve(TransportRequest $request, User $actor, array $data): void
    {
        if (! $request->isPending()) {
            throw SalesException::invalidTransportTransition($request->status, 'approved');
        }

        if (empty($data['driver_id'])) {
            throw SalesException::driverRequired();
        }

        $request->update([
            'status' => 'approved',
            'driver_id' => $data['driver_id'],
            'vehicle' => $data['vehicle'] ?: null,
            'scheduled_date' => $data['scheduled_date'] ?: null,
            'notes' => $data['notes'] ?: $request->notes,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        AuditLog::record('update', "Approved transport request {$request->reference}", $request, ['status' => 'pending'], ['status' => 'approved']);

        $this->notifyRequester(
            $request,
            $actor,
            'Transport Approved',
            "{$actor->name} approved {$request->reference}".($request->driver ? " with {$request->driver->name} driving." : '.'),
        );
    }

    public function dispatch(TransportRequest $request, User $actor): void
    {
        if ($request->status !== 'approved') {
            throw SalesException::invalidTransportTransition($request->status, 'in transit');
        }

        $request->update(['status' => 'in_transit', 'dispatched_at' => now()]);

        AuditLog::record('update', "Dispatched transport request {$request->reference}", $request, ['status' => 'approved'], ['status' => 'in_transit']);

        $this->notifyRequester(
            $request,
            $actor,
            'Transport Dispatched',
            "{$request->reference} is on its way to {$request->delivery_address}.",
        );

        if ($request->driver_id && $request->driver_id !== $actor->id) {
            HrNotification::notify(
                $request->driver_id,
                'transport_status',
                'Delivery Assigned',
                "You are delivering {$request->reference} to {$request->delivery_address}.",
                route('sales.transport'),
            );
        }
    }

    public function deliver(TransportRequest $request, User $actor): void
    {
        if ($request->status !== 'in_transit') {
            throw SalesException::invalidTransportTransition($request->status, 'delivered');
        }

        $request->update(['status' => 'delivered', 'delivered_at' => now()]);

        AuditLog::record('update', "Delivered transport request {$request->reference}", $request, ['status' => 'in_transit'], ['status' => 'delivered']);

        $this->notifyRequester(
            $request,
            $actor,
            'Transport Delivered',
            "{$request->reference} was delivered to {$request->delivery_address}.",
        );
    }

    public function cancel(TransportRequest $request, User $actor): void
    {
        if (! in_array($request->status, ['pending', 'approved'], true)) {
            throw SalesException::invalidTransportTransition($request->status, 'cancelled');
        }

        $previous = $request->status;
        $request->update(['status' => 'cancelled']);

        AuditLog::record('update', "Cancelled transport request {$request->reference}", $request, ['status' => $previous], ['status' => 'cancelled']);

        $this->notifyRequester(
            $request,
            $actor,
            'Transport Cancelled',
            "{$actor->name} cancelled transport request {$request->reference}.",
        );
    }

    /**
     * Tell everyone who can manage transport that there is something to move.
     */
    private function notifyManagers(TransportRequest $request): void
    {
        $managers = User::permission('manage transport')
            ->where('tenant_id', $request->tenant_id)
            ->get();

        foreach ($managers as $manager) {
            if ($manager->id === $request->requested_by) {
                continue;
            }

            HrNotification::notify(
                $manager->id,
                'transport_requested',
                'Transport Requested',
                "{$request->requester->name} needs {$request->items_summary} moved to {$request->delivery_address}.",
                route('sales.transport'),
            );
        }
    }

    private function notifyRequester(TransportRequest $request, User $actor, string $title, string $message): void
    {
        if ($request->requested_by === $actor->id) {
            return;
        }

        HrNotification::notify($request->requested_by, 'transport_status', $title, $message, route('sales.transport'));
    }
}
