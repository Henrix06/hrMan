<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\RentInvoice;
use App\Services\RealEstate\RentInvoicingService;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithPagination;

class RentInvoiceList extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    protected $queryString = [
        'statusFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if ($property === 'statusFilter') {
            $this->resetPage();
        }
    }

    public function markPaid(int $id): void
    {
        $this->authorize('real_estate.manage_invoices');
        $invoice = RentInvoice::with('lease.account')->findOrFail($id);

        try {
            app(RentInvoicingService::class)->markPaid($invoice, auth()->user());
            session()->flash('message', 'Invoice marked as paid.');
        } catch (InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $this->authorize('real_estate.view');

        $invoices = RentInvoice::with(['lease.property', 'lease.reTenant'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('due_date')
            ->paginate(15);

        return view('livewire.real-estate.rent-invoice-list', compact('invoices'));
    }
}
