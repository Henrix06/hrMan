<?php

namespace App\Livewire\RealEstate;

use App\Models\RealEstate\ConveyanceDocument;
use App\Models\RealEstate\Property;
use App\Models\RealEstate\PropertyUnit;
use Livewire\Component;
use Livewire\WithPagination;

class ConveyanceDocumentManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $unitId = null;
    public ?int $propertyId = null;
    public string $sellerName = '';
    public ?string $sellerIdNumber = null;
    public ?string $sellerAddress = null;
    public string $buyerName = '';
    public ?string $buyerIdNumber = null;
    public ?string $buyerAddress = null;
    public ?string $purchasePrice = null;
    public string $currency = 'ZWL';
    public ?string $agreementDate = null;
    public ?string $transferDate = null;
    public ?string $deedNumber = null;
    public ?string $standNumber = null;
    public ?string $surveyNumber = null;
    public ?string $conveyancerName = null;
    public ?string $conveyancerReference = null;
    public ?string $transferDuty = null;
    public ?string $conveyancingFee = null;
    public ?string $registrationFee = null;
    public ?string $otherFees = null;
    public ?string $notes = null;
    public string $status = 'draft';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function openCreate(?int $unitId = null, ?int $propertyId = null): void
    {
        $this->authorize('real_estate_accountant.manage');
        $this->reset(['editingId', 'unitId', 'propertyId', 'sellerName', 'sellerIdNumber', 'sellerAddress', 'buyerName', 'buyerIdNumber', 'buyerAddress', 'purchasePrice', 'currency', 'agreementDate', 'transferDate', 'deedNumber', 'standNumber', 'surveyNumber', 'conveyancerName', 'conveyancerReference', 'transferDuty', 'conveyancingFee', 'registrationFee', 'otherFees', 'notes', 'status']);
        $this->unitId = $unitId;
        $this->propertyId = $propertyId;
        $this->currency = 'ZWL';
        $this->status = 'draft';
        $this->agreementDate = now()->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('real_estate_accountant.manage');
        $document = ConveyanceDocument::findOrFail($id);
        $this->editingId = $id;
        $this->unitId = $document->unit_id;
        $this->propertyId = $document->property_id;
        $this->sellerName = $document->seller_name;
        $this->sellerIdNumber = $document->seller_id_number;
        $this->sellerAddress = $document->seller_address;
        $this->buyerName = $document->buyer_name;
        $this->buyerIdNumber = $document->buyer_id_number;
        $this->buyerAddress = $document->buyer_address;
        $this->purchasePrice = (string) $document->purchase_price;
        $this->currency = $document->currency;
        $this->agreementDate = $document->agreement_date->toDateString();
        $this->transferDate = $document->transfer_date?->toDateString();
        $this->deedNumber = $document->deed_number;
        $this->standNumber = $document->stand_number;
        $this->surveyNumber = $document->survey_number;
        $this->conveyancerName = $document->conveyancer_name;
        $this->conveyancerReference = $document->conveyancer_reference;
        $this->transferDuty = (string) ($document->transfer_duty ?? '');
        $this->conveyancingFee = (string) ($document->conveyancing_fee ?? '');
        $this->registrationFee = (string) ($document->registration_fee ?? '');
        $this->otherFees = (string) ($document->other_fees ?? '');
        $this->notes = $document->notes;
        $this->status = $document->status;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('real_estate_accountant.manage');

        $data = $this->validate([
            'unitId' => 'nullable|exists:re_property_units,id',
            'propertyId' => 'nullable|exists:re_properties,id',
            'sellerName' => 'required|string|max:200',
            'sellerIdNumber' => 'nullable|string|max:50',
            'sellerAddress' => 'nullable|string|max:500',
            'buyerName' => 'required|string|max:200',
            'buyerIdNumber' => 'nullable|string|max:50',
            'buyerAddress' => 'nullable|string|max:500',
            'purchasePrice' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'agreementDate' => 'required|date',
            'transferDate' => 'nullable|date|after:agreementDate',
            'deedNumber' => 'nullable|string|max:100',
            'standNumber' => 'nullable|string|max:100',
            'surveyNumber' => 'nullable|string|max:100',
            'conveyancerName' => 'nullable|string|max:200',
            'conveyancerReference' => 'nullable|string|max:100',
            'transferDuty' => 'nullable|numeric|min:0',
            'conveyancingFee' => 'nullable|numeric|min:0',
            'registrationFee' => 'nullable|numeric|min:0',
            'otherFees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'status' => 'required|in:draft,pending_approval,in_progress,transferred,cancelled',
        ]);

        $saveData = [
            'company_tenant_id' => auth()->id(),
            'unit_id' => $data['unitId'],
            'property_id' => $data['propertyId'],
            'seller_name' => $data['sellerName'],
            'seller_id_number' => $data['sellerIdNumber'],
            'seller_address' => $data['sellerAddress'],
            'buyer_name' => $data['buyerName'],
            'buyer_id_number' => $data['buyerIdNumber'],
            'buyer_address' => $data['buyerAddress'],
            'purchase_price' => $data['purchasePrice'],
            'currency' => $data['currency'],
            'agreement_date' => $data['agreementDate'],
            'transfer_date' => $data['transferDate'],
            'deed_number' => $data['deedNumber'],
            'stand_number' => $data['standNumber'],
            'survey_number' => $data['surveyNumber'],
            'conveyancer_name' => $data['conveyancerName'],
            'conveyancer_reference' => $data['conveyancerReference'],
            'transfer_duty' => $data['transferDuty'],
            'conveyancing_fee' => $data['conveyancingFee'],
            'registration_fee' => $data['registrationFee'],
            'other_fees' => $data['otherFees'],
            'notes' => $data['notes'],
            'status' => $data['status'],
        ];

        if ($this->editingId) {
            $document = ConveyanceDocument::findOrFail($this->editingId);
            $document->update($saveData);
            session()->flash('message', 'Conveyance document updated.');
        } else {
            ConveyanceDocument::create(array_merge($saveData, [
                'created_by' => auth()->id(),
            ]));
            session()->flash('message', 'Conveyance document created.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('real_estate_accountant.view');

        $documents = ConveyanceDocument::with(['unit.property', 'property', 'creator'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhere('seller_name', 'like', "%{$this->search}%")
                  ->orWhere('buyer_name', 'like', "%{$this->search}%")
                  ->orWhere('deed_number', 'like', "%{$this->search}%")
                  ->orWhere('stand_number', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $properties = Property::orderBy('name')->pluck('name', 'id');
        $units = PropertyUnit::orderBy('unit_number')->pluck('unit_number', 'id');

        return view('livewire.real-estate.conveyance-document-manager', compact('documents', 'properties', 'units'));
    }
}
