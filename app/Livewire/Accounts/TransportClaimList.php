<?php

namespace App\Livewire\Accounts;

use App\Actions\Accounts\ReviewClaim;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\TransportClaim;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class TransportClaimList extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $search = '';

    // Form modal
    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public string $travel_date = '';
    public string $origin = '';
    public string $destination = '';
    public string $purpose = '';
    public string $transport_mode = 'taxi';
    public string $distance_km = '';
    public string $claim_amount = '';
    public string $tc_notes = '';
    public ?int $account_id = null;

    // Review modal
    public bool $showReviewModal = false;
    public ?int $reviewingId = null;
    public string $review_action = '';
    public string $approved_amount = '';
    public string $review_notes = '';

    protected $queryString = [
        'statusFilter' => ['except' => ''],
        'search'       => ['except' => ''],
    ];

    public function updated(string $prop): void
    {
        if (in_array($prop, ['search', 'statusFilter'])) $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $claim = TransportClaim::findOrFail($id);

        if ($claim->user_id !== auth()->id() && !auth()->user()->isHRAdmin()) {
            session()->flash('error', 'You can only edit your own claims.');
            return;
        }

        if ($claim->status !== 'draft') {
            session()->flash('error', 'Only draft claims can be edited.');
            return;
        }

        $this->editingId = $id;
        $this->editing = true;
        $this->travel_date = $claim->travel_date->format('Y-m-d');
        $this->origin = $claim->origin;
        $this->destination = $claim->destination;
        $this->purpose = $claim->purpose;
        $this->transport_mode = $claim->transport_mode;
        $this->distance_km = (string) ($claim->distance_km ?? '');
        $this->claim_amount = (string) $claim->claim_amount;
        $this->tc_notes = $claim->notes ?? '';
        $this->account_id = $claim->account_id;
        $this->showModal = true;
    }

    public function save(string $submitStatus = 'draft'): void
    {
        $this->validate([
            'travel_date'    => 'required|date',
            'origin'         => 'required|string|max:200',
            'destination'    => 'required|string|max:200',
            'purpose'        => 'required|string|max:500',
            'transport_mode' => 'required|in:taxi,bus,fuel,flight,other',
            'distance_km'    => 'nullable|numeric|min:0',
            'claim_amount'   => 'required|numeric|min:0.01',
            'tc_notes'       => 'nullable|string|max:1000',
            'account_id'     => 'nullable|exists:accounts,id',
        ]);

        $status = in_array($submitStatus, ['draft', 'pending']) ? $submitStatus : 'draft';

        $payload = [
            'travel_date'    => $this->travel_date,
            'origin'         => $this->origin,
            'destination'    => $this->destination,
            'purpose'        => $this->purpose,
            'transport_mode' => $this->transport_mode,
            'distance_km'    => $this->distance_km ?: null,
            'claim_amount'   => (float) $this->claim_amount,
            'notes'          => $this->tc_notes ?: null,
            'account_id'     => $this->account_id,
            'status'         => $status,
        ];

        if ($this->editing && $this->editingId) {
            $claim = TransportClaim::findOrFail($this->editingId);
            $claim->update($payload);
            AuditLog::record('update', "Updated transport claim {$claim->reference}", $claim);
        } else {
            $claim = TransportClaim::create(array_merge($payload, [
                'reference' => TransportClaim::generateReference(),
                'user_id'   => auth()->id(),
            ]));
            AuditLog::record('create', "Submitted transport claim {$claim->reference}", $claim);
        }

        session()->flash('message', $status === 'pending' ? 'Claim submitted for approval.' : 'Draft saved.');
        $this->showModal = false;
        $this->resetPage();
    }

    public function cancel(int $id): void
    {
        $claim = TransportClaim::findOrFail($id);

        if ($claim->user_id !== auth()->id() && !auth()->user()->isHRAdmin()) {
            session()->flash('error', 'You can only cancel your own claims.');
            return;
        }

        if (!in_array($claim->status, ['draft', 'pending'])) {
            session()->flash('error', 'Only draft or pending claims can be cancelled.');
            return;
        }

        $claim->delete();
        session()->flash('message', 'Claim removed.');
    }

    public function openReview(int $id, string $action): void
    {
        $this->authorize('approve claims');
        $claim = TransportClaim::findOrFail($id);
        $this->reviewingId = $id;
        $this->review_action = $action;
        $this->approved_amount = (string) $claim->claim_amount;
        $this->review_notes = '';
        $this->showReviewModal = true;
    }

    public function submitReview(): void
    {
        $this->authorize('approve claims');

        $this->validate([
            'approved_amount' => $this->review_action === 'approve' ? 'required|numeric|min:0.01' : 'nullable',
            'review_notes'    => $this->review_action === 'reject' ? 'required|string|max:500' : 'nullable|string|max:500',
        ]);

        $claim = TransportClaim::with('account', 'employee')->findOrFail($this->reviewingId);

        try {
            $reviewer = app(ReviewClaim::class);
            if ($this->review_action === 'approve') {
                $reviewer->approve($claim, auth()->user(), (float) $this->approved_amount, $this->review_notes ?: null);
                session()->flash('message', 'Claim approved.');
            } else {
                $reviewer->reject($claim, auth()->user(), $this->review_notes ?: null);
                session()->flash('message', 'Claim rejected.');
            }
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showReviewModal = false;
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'travel_date', 'origin', 'destination', 'purpose', 'distance_km', 'claim_amount', 'tc_notes', 'account_id']);
        $this->transport_mode = 'taxi';
    }

    public function render()
    {
        $user = auth()->user();
        $canReview = $user->can('approve claims');

        $claims = TransportClaim::with(['employee:id,name', 'account:id,name,code', 'reviewer:id,name'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhereHas('employee', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->unless($canReview, fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->paginate(15);

        $accounts = Account::where('active', true)->orderBy('name')->get(['id', 'name', 'code', 'balance']);

        return view('livewire.accounts.transport', compact('claims', 'accounts', 'canReview'));
    }
}
