@php
    $currency = $order->currency;
    $money = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $rate = rtrim(rtrim(number_format((float) $order->tax_rate, 2), '0'), '.');
    $isDraft = $order->status === 'draft';
    $deliverTo = $order->delivery_address ?: $order->customer_address;
@endphp

<div>
    <div class="toolbar no-print">
        <div>
            <a href="{{ route('sales.orders') }}" class="btn">&larr; Back to orders</a>
            <span class="hint">Choose <strong>Print</strong>, then “Save as PDF” to keep a copy.</span>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('sales.transport') }}" class="btn">Transport requests</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        </div>
    </div>

    <div class="sheet">
        <header class="doc-head">
            <div>
                <div class="brand">{{ $company?->name ?? config('app.name', 'uMsizi') }}</div>
                <div class="muted small">{{ $company?->url() }}</div>
                <div class="muted small">Logged by {{ $order->seller?->name ?? '—' }}</div>
            </div>
            <div class="doc-meta">
                <div class="doc-kind">Sales Order</div>
                <div class="doc-ref">{{ $order->reference }}</div>
                <div class="muted small">Issued {{ $order->order_date->format('j M Y') }}</div>
                <div style="margin-top: 6px;">
                    <span class="badge {{ $isDraft ? 'badge-draft' : '' }}">{{ str_replace('_', ' ', $order->status) }}</span>
                </div>
            </div>
        </header>

        @if($isDraft)
        <div class="notice">
            <strong>Draft.</strong> This order has not been confirmed yet, so the figures below may still change.
        </div>
        @endif

        <div class="parties">
            <div class="party">
                <h3>Customer</h3>
                <div style="font-weight: 700;">{{ $order->customer_name }}</div>
                @if($order->customer_email)<div>{{ $order->customer_email }}</div>@endif
                @if($order->customer_phone)<div>{{ $order->customer_phone }}</div>@endif
                @if($order->customer_address)<div class="muted">{{ $order->customer_address }}</div>@endif
            </div>
            <div class="party">
                <h3>Deliver To</h3>
                <div style="font-weight: 700;">{{ $deliverTo ?: 'Not specified' }}</div>
                @if($order->customer_phone)<div class="muted small">{{ $order->customer_phone }}</div>@endif
            </div>
        </div>

        <div class="section">
            <h3>Items</h3>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th class="right">Qty</th>
                        <th class="right">Unit Price</th>
                        <th class="right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="right nowrap">{{ $quantity($item->quantity) }}</td>
                        <td class="right nowrap">{{ $money($item->unit_price) }}</td>
                        <td class="right nowrap">{{ $money($item->line_total) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="muted">No line items.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="totals">
                <div>
                    <span class="muted">Subtotal</span>
                    <span>{{ $money($order->subtotal) }}</span>
                </div>
                <div>
                    <span class="muted">Tax{{ (float) $order->tax_rate > 0 ? ' ('.$rate.'%)' : '' }}</span>
                    <span>{{ $money($order->tax_amount) }}</span>
                </div>
                <div class="grand">
                    <span>Total</span>
                    <span>{{ $money($order->total_amount) }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Transport</h3>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width: 22%;">Reference</th>
                        <th>Route</th>
                        <th style="width: 22%;">Driver &amp; Vehicle</th>
                        <th style="width: 16%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->transportRequests as $request)
                    <tr>
                        <td class="nowrap">{{ $request->reference }}</td>
                        <td>
                            {{ $request->pickup_address }} &rarr; {{ $request->delivery_address }}
                            @if($request->scheduled_date)
                                <div class="muted small">Scheduled {{ $request->scheduled_date->format('j M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $request->driver?->name ?? 'No driver assigned' }}
                            @if($request->vehicle)<div class="muted small">{{ $request->vehicle }}</div>@endif
                        </td>
                        <td class="nowrap">
                            <span class="badge">{{ str_replace('_', ' ', $request->status) }}</span>
                            @if($request->delivered_at)
                                <div class="muted small">{{ $request->delivered_at->format('j M Y') }}</div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="muted">No transport has been arranged for this order.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($order->notes)
        <div class="section">
            <h3>Notes</h3>
            <div>{{ $order->notes }}</div>
        </div>
        @endif

        <div class="signatures">
            <div class="signature">
                <div class="line"></div>
                <div class="caption">Received by — name, signature &amp; date</div>
            </div>
            <div class="signature">
                <div class="line"></div>
                <div class="caption">Delivered by — name, signature &amp; date</div>
            </div>
        </div>

        <div class="doc-footer">
            <span>{{ $order->reference }} · {{ $order->customer_name }}</span>
            <span>Printed {{ now()->format('j M Y H:i') }}</span>
        </div>
    </div>
</div>
