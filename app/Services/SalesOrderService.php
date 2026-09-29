<?php

namespace App\Services;

use App\Exceptions\Sales\SalesException;
use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Logging a sale: the order, its line items, the totals they add up to and the
 * lifecycle that takes an order from draft to fulfilled.
 */
class SalesOrderService
{
    /**
     * Line items with blank rows removed, quantities and prices cast to numbers.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{description: string, quantity: float, unit_price: float, line_total: float, position: int}>
     */
    public function normaliseItems(array $items): array
    {
        $normalised = [];
        $position = 0;

        foreach ($items as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            // A completely blank row is not an error — it is simply dropped.
            if ($description === '' && $quantity <= 0 && $unitPrice <= 0) {
                continue;
            }

            $normalised[] = [
                'description' => $description,
                'quantity' => $quantity > 0 ? $quantity : 1.0,
                'unit_price' => $unitPrice,
                'line_total' => $this->lineTotal($quantity > 0 ? $quantity : 1.0, $unitPrice),
                'position' => $position++,
            ];
        }

        return $normalised;
    }

    public function lineTotal(float $quantity, float $unitPrice): float
    {
        return round($quantity * $unitPrice, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items  already normalised
     * @return array{subtotal: float, tax_amount: float, total_amount: float}
     */
    public function calculateTotals(array $items, float $taxRate): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float) $item['line_total'];
        }

        $subtotal = round($subtotal, 2);
        $taxAmount = round($subtotal * $taxRate / 100, 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => round($subtotal + $taxAmount, 2),
        ];
    }

    /**
     * Log a new order. A draft can be edited later; a confirmed order is ready
     * to be picked up for transport.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(User $seller, array $data, array $items, string $status = 'draft'): SalesOrder
    {
        $items = $this->normaliseItems($items);

        if ($items === []) {
            throw SalesException::noItems();
        }

        $status = in_array($status, ['draft', 'confirmed'], true) ? $status : 'draft';
        $taxRate = (float) ($data['tax_rate'] ?? config('sales.default_tax_rate', 0));
        $totals = $this->calculateTotals($items, $taxRate);

        $order = DB::transaction(function () use ($seller, $data, $items, $status, $taxRate, $totals) {
            $order = SalesOrder::create([
                'reference' => SalesOrder::generateReference(),
                'sold_by' => $seller->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?: null,
                'customer_phone' => $data['customer_phone'] ?: null,
                'customer_address' => $data['customer_address'] ?: null,
                'delivery_address' => $data['delivery_address'] ?: null,
                'currency' => $data['currency'] ?? config('sales.currency', 'USD'),
                'tax_rate' => $taxRate,
                'status' => $status,
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?: null,
                'confirmed_at' => $status === 'confirmed' ? now() : null,
                'tenant_id' => $seller->tenant_id,
            ] + $totals);

            $this->replaceItems($order, $items);

            AuditLog::record('create', "Logged sales order {$order->reference} for {$order->customer_name}", $order);

            return $order;
        });

        return $order->load('items');
    }

    /**
     * Replace the details and line items of a draft order.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function update(SalesOrder $order, array $data, array $items, User $actor): SalesOrder
    {
        if (! $order->isEditable()) {
            throw SalesException::notEditable();
        }

        $items = $this->normaliseItems($items);

        if ($items === []) {
            throw SalesException::noItems();
        }

        $taxRate = (float) ($data['tax_rate'] ?? config('sales.default_tax_rate', 0));
        $totals = $this->calculateTotals($items, $taxRate);
        $old = $order->toArray();

        DB::transaction(function () use ($order, $data, $items, $taxRate, $totals) {
            $order->update([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?: null,
                'customer_phone' => $data['customer_phone'] ?: null,
                'customer_address' => $data['customer_address'] ?: null,
                'delivery_address' => $data['delivery_address'] ?: null,
                'tax_rate' => $taxRate,
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?: null,
            ] + $totals);

            $this->replaceItems($order, $items);
        });

        AuditLog::record('update', "Updated sales order {$order->reference}", $order, $old, $order->fresh()->toArray());

        return $order->fresh()->load('items');
    }

    public function confirm(SalesOrder $order, User $actor): void
    {
        if ($order->status !== 'draft') {
            throw SalesException::invalidTransition($order->status, 'confirmed');
        }

        $order->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        AuditLog::record('update', "Confirmed sales order {$order->reference}", $order, ['status' => 'draft'], ['status' => 'confirmed']);

        $this->notifySeller(
            $order,
            $actor,
            'Sales Order Confirmed',
            "{$actor->name} confirmed order {$order->reference} ({$order->customer_name}).",
        );
    }

    public function fulfill(SalesOrder $order, User $actor): void
    {
        if (! $order->canBeFulfilled()) {
            throw SalesException::invalidTransition($order->status, 'fulfilled');
        }

        $order->update(['status' => 'fulfilled', 'fulfilled_at' => now()]);

        AuditLog::record('update', "Fulfilled sales order {$order->reference}", $order, ['status' => 'confirmed'], ['status' => 'fulfilled']);

        $this->notifySeller(
            $order,
            $actor,
            'Sales Order Fulfilled',
            "{$actor->name} marked order {$order->reference} as fulfilled.",
        );
    }

    public function cancel(SalesOrder $order, User $actor): void
    {
        if (! in_array($order->status, ['draft', 'confirmed'], true)) {
            throw SalesException::invalidTransition($order->status, 'cancelled');
        }

        $previous = $order->status;
        $order->update(['status' => 'cancelled']);

        AuditLog::record('update', "Cancelled sales order {$order->reference}", $order, ['status' => $previous], ['status' => 'cancelled']);

        $this->notifySeller(
            $order,
            $actor,
            'Sales Order Cancelled',
            "{$actor->name} cancelled order {$order->reference} ({$order->customer_name}).",
        );
    }

    /**
     * @param  array<int, array{description: string, quantity: float, unit_price: float, line_total: float, position: int}>  $items
     */
    private function replaceItems(SalesOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $order->items()->create($item);
        }
    }

    private function notifySeller(SalesOrder $order, User $actor, string $title, string $message): void
    {
        if ($order->sold_by === $actor->id) {
            return;
        }

        HrNotification::notify($order->sold_by, 'sales_order', $title, $message, route('sales.orders'));
    }
}
