<?php

namespace App\Livewire\Sales;

use App\Exceptions\Sales\SalesException;
use App\Models\SalesOrder;
use App\Models\TransportRequest;
use App\Services\SalesOrderService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class SalesOrderList extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';

    public string $statusFilter = '';

    // Create/edit modal
    public bool $showModal = false;

    public bool $editing = false;

    public ?int $editingId = null;

    public string $customer_name = '';

    public string $customer_email = '';

    public string $customer_phone = '';

    public string $customer_address = '';

    public string $delivery_address = '';

    public string $order_date = '';

    public string $tax_rate = '';

    public string $order_notes = '';

    public array $items = [];

    // View modal
    public bool $showView = false;

    public ?SalesOrder $activeOrder = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->order_date = now()->toDateString();
        $this->tax_rate = (string) config('sales.default_tax_rate', 0);
        $this->resetItems();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    /* ---------------------------------------------------------------------
     |  Create / edit
     * ------------------------------------------------------------------- */

    public function openCreate(): void
    {
        $this->authorize('create sales order');

        $this->resetForm();
        $this->editing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $order = $this->visibleOrder($id);
        $this->authorize('create sales order');

        if (! $order->isEditable()) {
            session()->flash('error', 'Only draft orders can be changed.');

            return;
        }

        $this->editingId = $id;
        $this->editing = true;
        $this->customer_name = $order->customer_name;
        $this->customer_email = $order->customer_email ?? '';
        $this->customer_phone = $order->customer_phone ?? '';
        $this->customer_address = $order->customer_address ?? '';
        $this->delivery_address = $order->delivery_address ?? '';
        $this->order_date = $order->order_date->format('Y-m-d');
        $this->tax_rate = (string) $order->tax_rate;
        $this->order_notes = $order->notes ?? '';
        $this->items = $order->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => (string) $item->quantity,
            'unit_price' => (string) $item->unit_price,
        ])->all();

        $this->showModal = true;
    }

    public function addItemRow(): void
    {
        $this->items[] = ['description' => '', 'quantity' => '1', 'unit_price' => ''];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->resetItems();
        }
    }

    public function save(string $submitStatus = 'draft'): void
    {
        $this->authorize('create sales order');

        // Drop half-filled blank rows so they cannot trip validation.
        $this->items = array_values(array_filter($this->items, fn ($item) => trim((string) ($item['description'] ?? '')) !== '' || (float) ($item['unit_price'] ?? 0) > 0));

        $this->validate([
            'customer_name' => 'required|string|max:200',
            'customer_email' => 'nullable|email|max:200',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string|max:500',
            'delivery_address' => 'nullable|string|max:500',
            'order_date' => 'required|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'order_notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:200',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $data = [
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_address' => $this->customer_address,
            'delivery_address' => $this->delivery_address,
            'order_date' => $this->order_date,
            'tax_rate' => (float) ($this->tax_rate ?: 0),
            'notes' => $this->order_notes,
        ];

        $service = app(SalesOrderService::class);

        try {
            if ($this->editing && $this->editingId) {
                $service->update($this->visibleOrder($this->editingId), $data, $this->items, auth()->user());
                session()->flash('message', 'Order updated.');
            } else {
                $service->create(auth()->user(), $data, $this->items, $submitStatus);
                session()->flash('message', $submitStatus === 'confirmed' ? 'Order logged and confirmed.' : 'Draft order logged.');
            }
        } catch (SalesException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->showModal = false;
        $this->resetPage();
    }

    /* ---------------------------------------------------------------------
     |  Lifecycle
     * ------------------------------------------------------------------- */

    public function confirm(int $id): void
    {
        $this->authorize('manage sales');

        $this->runLifecycle(fn () => app(SalesOrderService::class)->confirm($this->visibleOrder($id), auth()->user()), 'Order confirmed.');
    }

    public function fulfill(int $id): void
    {
        $this->authorize('manage sales');

        $this->runLifecycle(fn () => app(SalesOrderService::class)->fulfill($this->visibleOrder($id), auth()->user()), 'Order marked as fulfilled.');
    }

    public function cancel(int $id): void
    {
        $order = $this->visibleOrder($id);

        if ($order->sold_by !== auth()->id()) {
            $this->authorize('manage sales');
        }

        $this->runLifecycle(fn () => app(SalesOrderService::class)->cancel($order, auth()->user()), 'Order cancelled.');
    }

    private function runLifecycle(callable $action, string $successMessage): void
    {
        try {
            $action();
            session()->flash('message', $successMessage);
        } catch (SalesException $e) {
            session()->flash('error', $e->getMessage());
        }

        if ($this->activeOrder) {
            $this->activeOrder = $this->activeOrder->fresh()->load(['items', 'seller:id,name', 'transportRequests.driver:id,name']);
        }

        $this->resetPage();
    }

    public function view(int $id): void
    {
        $this->activeOrder = $this->visibleOrder($id);
        $this->showView = true;
    }

    /* ---------------------------------------------------------------------
     |  Helpers
     | ------------------------------------------------------------------- */

    /**
     * Totals for the modal, refreshed as the item rows change.
     *
     * @return array{subtotal: float, tax_amount: float, total_amount: float}
     */
    public function draftTotals(): array
    {
        $service = app(SalesOrderService::class);

        return $service->calculateTotals(
            $service->normaliseItems($this->items),
            (float) ($this->tax_rate ?: 0),
        );
    }

    private function visibleOrder(int $id): SalesOrder
    {
        $order = SalesOrder::with(['items', 'seller:id,name', 'transportRequests.driver:id,name'])->findOrFail($id);

        // 404 rather than 403 so a colleague cannot probe which orders exist.
        abort_unless(auth()->user()->can('view', $order), 404);

        return $order;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'customer_name', 'customer_email', 'customer_phone',
            'customer_address', 'delivery_address', 'order_notes',
        ]);

        $this->order_date = now()->toDateString();
        $this->tax_rate = (string) config('sales.default_tax_rate', 0);
        $this->resetItems();
    }

    private function resetItems(): void
    {
        $this->items = [['description' => '', 'quantity' => '1', 'unit_price' => '']];
    }

    /**
     * Orders this user is allowed to see.
     */
    private function visibleOrders(): Builder
    {
        $user = auth()->user();

        return SalesOrder::query()
            ->unless($user->can('manage sales'), fn ($q) => $q->where('sold_by', $user->id));
    }

    public function render()
    {
        $this->authorize('view sales');

        $user = auth()->user();
        $canManageSales = $user->can('manage sales');

        $orders = $this->visibleOrders()
            ->with(['seller:id,name', 'items', 'transportRequests'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('customer_name', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15);

        $soldThisMonth = $this->visibleOrders()
            ->whereYear('order_date', now()->year)
            ->whereMonth('order_date', now()->month);

        $stats = [
            'month_value' => (float) (clone $soldThisMonth)->whereIn('status', ['confirmed', 'fulfilled'])->sum('total_amount'),
            'month_count' => (clone $soldThisMonth)->count(),
            'open_transport' => TransportRequest::open()
                ->unless($canManageSales, fn ($q) => $q->where('requested_by', $user->id))
                ->count(),
        ];

        $currency = (string) config('sales.currency', 'USD');

        return view('livewire.sales.orders', compact('orders', 'stats', 'currency', 'canManageSales'));
    }
}
