<?php

namespace App\Livewire\Sales;

use App\Models\SalesOrder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.print')]
class SalesOrderDocument extends Component
{
    public SalesOrder $order;

    public function mount(SalesOrder $order): void
    {
        // The same rule as the orders page: you only ever print an order you
        // logged yourself, or one belonging to a company you can manage.
        abort_unless(auth()->user()?->can('view', $order), 404);

        $this->order = $order->load([
            'items',
            'seller:id,name',
            'transportRequests.driver:id,name',
        ]);
    }

    public function render()
    {
        return view('livewire.sales.document', [
            'company' => auth()->user()?->tenant,
        ])->title('Sales Order '.$this->order->reference);
    }
}
