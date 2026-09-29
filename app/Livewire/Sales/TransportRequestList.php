<?php

namespace App\Livewire\Sales;

use App\Exceptions\Sales\SalesException;
use App\Models\SalesOrder;
use App\Models\TransportRequest;
use App\Models\User;
use App\Services\TransportRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TransportRequestList extends Component
{
    use WithPagination;

    /** Deep link from the orders page: ?order=12 opens a prefilled request. */
    public ?int $order = null;

    // Filters
    public string $search = '';

    public string $statusFilter = '';

    // Create modal
    public bool $showModal = false;

    public ?int $order_id = null;

    public string $pickup_address = '';

    public string $delivery_address = '';

    public string $pickup_contact = '';

    public string $delivery_contact = '';

    public string $items_summary = '';

    public string $scheduled_date = '';

    public string $transport_notes = '';

    // Approval modal
    public bool $showApproveModal = false;

    public ?int $approvingId = null;

    public ?int $driver_id = null;

    public string $vehicle = '';

    public string $approve_scheduled_date = '';

    public string $approve_notes = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->scheduled_date = now()->addDay()->toDateString();

        // The orders page deep links here with ?order=… to start a prefilled request.
        $orderId = request()->integer('order') ?: $this->order;

        if ($orderId) {
            $this->startFromOrder($orderId);
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    /* ---------------------------------------------------------------------
     |  Requesting transport
     * ------------------------------------------------------------------- */

    public function openCreate(): void
    {
        $this->authorize('request transport');

        $this->resetRequestForm();
        $this->showModal = true;
    }

    /**
     * Prefill a new request from a sales order the user has logged.
     */
    public function startFromOrder(int $orderId): void
    {
        $this->authorize('request transport');

        try {
            $order = $this->requestableOrder($orderId);
        } catch (SalesException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->resetRequestForm();
        $this->fillFromOrder($order);
        $this->showModal = true;
    }

    /**
     * Keep the address fields in step with the chosen order.
     */
    public function updatedOrderId(): void
    {
        if (! $this->order_id) {
            return;
        }

        try {
            $order = $this->requestableOrder((int) $this->order_id);
        } catch (SalesException) {
            return;
        }

        $this->pickup_address = $this->pickup_address ?: (string) ($order->customer_address ?? '');
        $this->delivery_address = $this->delivery_address ?: (string) ($order->delivery_address ?: $order->customer_address ?: '');
        $this->pickup_contact = $this->pickup_contact ?: (string) ($order->customer_phone ?? '');
        $this->delivery_contact = $this->delivery_contact ?: (string) ($order->customer_phone ?? '');
        $this->items_summary = $this->items_summary ?: $order->itemsSummary();
    }

    public function requestTransport(): void
    {
        $this->authorize('request transport');

        $this->validate([
            'order_id' => ['required', Rule::exists('sales_orders', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'pickup_address' => 'required|string|max:300',
            'delivery_address' => 'required|string|max:300',
            'pickup_contact' => 'nullable|string|max:100',
            'delivery_contact' => 'nullable|string|max:100',
            'items_summary' => 'nullable|string|max:500',
            'scheduled_date' => 'nullable|date',
            'transport_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $order = SalesOrder::with('items')->findOrFail($this->order_id);

            app(TransportRequestService::class)->requestFromOrder($order, auth()->user(), [
                'pickup_address' => $this->pickup_address,
                'delivery_address' => $this->delivery_address,
                'pickup_contact' => $this->pickup_contact,
                'delivery_contact' => $this->delivery_contact,
                'items_summary' => $this->items_summary,
                'scheduled_date' => $this->scheduled_date,
                'notes' => $this->transport_notes,
            ]);

            session()->flash('message', 'Transport request submitted.');
        } catch (SalesException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->showModal = false;
        $this->order = null;
        $this->resetPage();
    }

    /* ---------------------------------------------------------------------
     |  Managing transport
     * ------------------------------------------------------------------- */

    public function openApprove(int $id): void
    {
        $this->authorize('manage transport');

        $request = $this->visibleRequest($id);
        $this->approvingId = $id;
        $this->driver_id = $request->driver_id;
        $this->vehicle = $request->vehicle ?? '';
        $this->approve_scheduled_date = $request->scheduled_date?->format('Y-m-d') ?? $this->scheduled_date;
        $this->approve_notes = $request->notes ?? '';
        $this->showApproveModal = true;
    }

    public function submitApproval(): void
    {
        $this->authorize('manage transport');

        $this->validate([
            'driver_id' => ['required', Rule::exists('users', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'vehicle' => 'nullable|string|max:100',
            'approve_scheduled_date' => 'nullable|date',
            'approve_notes' => 'nullable|string|max:1000',
        ]);

        try {
            app(TransportRequestService::class)->approve(
                $this->visibleRequest((int) $this->approvingId),
                auth()->user(),
                [
                    'driver_id' => $this->driver_id,
                    'vehicle' => $this->vehicle,
                    'scheduled_date' => $this->approve_scheduled_date,
                    'notes' => $this->approve_notes,
                ],
            );

            session()->flash('message', 'Transport approved.');
        } catch (SalesException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->showApproveModal = false;
        $this->resetPage();
    }

    public function markInTransit(int $id): void
    {
        $this->authorize('manage transport');

        $this->runAction(fn (TransportRequestService $service, TransportRequest $request, User $actor) => $service->dispatch($request, $actor), $id, 'Transport dispatched.');
    }

    public function deliver(int $id): void
    {
        $this->authorize('manage transport');

        $this->runAction(fn (TransportRequestService $service, TransportRequest $request, User $actor) => $service->deliver($request, $actor), $id, 'Marked as delivered.');
    }

    public function cancel(int $id): void
    {
        $request = $this->visibleRequest($id);

        if ($request->requested_by !== auth()->id()) {
            $this->authorize('manage transport');
        }

        $this->runAction(fn (TransportRequestService $service, TransportRequest $request, User $actor) => $service->cancel($request, $actor), $id, 'Transport request cancelled.');
    }

    private function runAction(callable $action, int $id, string $successMessage): void
    {
        try {
            $action(app(TransportRequestService::class), $this->visibleRequest($id), auth()->user());
            session()->flash('message', $successMessage);
        } catch (SalesException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->resetPage();
    }

    /* ---------------------------------------------------------------------
     |  Helpers
     | ------------------------------------------------------------------- */

    /**
     * A request this user is allowed to touch, or a 404.
     */
    private function visibleRequest(int $id): TransportRequest
    {
        $request = TransportRequest::with(['order:id,reference,customer_name', 'requester:id,name', 'driver:id,name'])->findOrFail($id);

        $user = auth()->user();

        abort_unless($this->canSeeRequest($request, $user), 404);

        return $request;
    }

    /**
     * Whoever asked for the transport, whoever is driving it, and anyone who
     * runs the transport desk.
     */
    private function canSeeRequest(TransportRequest $request, User $user): bool
    {
        return $request->requested_by === $user->id
            || $request->driver_id === $user->id
            || $user->can('manage transport');
    }

    /**
     * A confirmed order belonging to the user that has nothing en route yet.
     */
    private function requestableOrder(int $id): SalesOrder
    {
        $order = SalesOrder::with('items')->findOrFail($id);
        $user = auth()->user();

        if ($order->sold_by !== $user->id && ! $user->can('manage sales')) {
            throw SalesException::notYourOrder();
        }

        if (! in_array($order->status, ['confirmed', 'fulfilled'], true)) {
            throw SalesException::transportNeedsConfirmedOrder($order->status);
        }

        if ($order->hasOpenTransportRequest()) {
            throw SalesException::duplicateTransportRequest();
        }

        return $order;
    }

    private function fillFromOrder(SalesOrder $order): void
    {
        $this->order_id = $order->id;
        $this->pickup_address = (string) ($order->customer_address ?? '');
        $this->delivery_address = (string) ($order->delivery_address ?: $order->customer_address ?: '');
        $this->pickup_contact = (string) ($order->customer_phone ?? '');
        $this->delivery_contact = (string) ($order->customer_phone ?? '');
        $this->items_summary = $order->itemsSummary();
    }

    private function resetRequestForm(): void
    {
        $this->reset([
            'order_id', 'pickup_address', 'delivery_address', 'pickup_contact',
            'delivery_contact', 'items_summary', 'transport_notes',
        ]);

        $this->scheduled_date = now()->addDay()->toDateString();
    }

    /**
     * Requests this user is allowed to see.
     */
    private function visibleRequests(): Builder
    {
        $user = auth()->user();

        return TransportRequest::query()
            ->unless($user->can('manage transport'), fn ($q) => $q->where(fn ($q) => $q
                ->where('requested_by', $user->id)
                ->orWhere('driver_id', $user->id)));
    }

    public function render()
    {
        $this->authorize('view sales');

        $user = auth()->user();
        $canManageTransport = $user->can('manage transport');

        $requests = $this->visibleRequests()
            ->with(['order:id,reference,customer_name', 'requester:id,name', 'driver:id,name'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('delivery_address', 'like', "%{$this->search}%")
                    ->orWhereHas('order', fn ($q) => $q->where('reference', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        // Confirmed orders that still need moving — what the request form offers.
        $requestableOrders = SalesOrder::with('items')
            ->whereIn('status', ['confirmed', 'fulfilled'])
            ->when(! $user->can('manage sales'), fn ($q) => $q->where('sold_by', $user->id))
            ->whereDoesntHave('transportRequests', fn ($q) => $q->whereIn('status', SalesOrder::OPEN_TRANSPORT_STATUSES))
            ->orderByDesc('order_date')
            ->get();

        $drivers = User::where('tenant_id', $user->tenant_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.sales.transport', compact('requests', 'requestableOrders', 'drivers', 'canManageTransport'));
    }
}
