<?php

namespace App\Livewire\Accounts;

use App\Actions\Accounts\ApproveProcurement;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\ProcurementRequest;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class ProcurementList extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $search = '';

    // Create/edit modal
    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public string $vendor = '';
    public string $category = 'supplies';
    public string $priority = 'medium';
    public string $estimated_cost = '';
    public ?int $account_id = null;
    public string $required_by = '';
    public string $prq_notes = '';

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

    // ── Submit ────────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $req = ProcurementRequest::findOrFail($id);

        if ($req->requested_by !== auth()->id() && !auth()->user()->isHRAdmin()) {
            session()->flash('error', 'You can only edit your own requests.');
            return;
        }

        if (!$req->isDraft()) {
            session()->flash('error', 'Only draft requests can be edited.');
            return;
        }

        $this->editingId = $id;
        $this->editing = true;
        $this->title = $req->title;
        $this->description = $req->description;
        $this->vendor = $req->vendor ?? '';
        $this->category = $req->category;
        $this->priority = $req->priority;
        $this->estimated_cost = (string) $req->estimated_cost;
        $this->account_id = $req->account_id;
        $this->required_by = $req->required_by?->format('Y-m-d') ?? '';
        $this->prq_notes = '';
        $this->showModal = true;
    }

    public function save(string $submitStatus = 'draft'): void
    {
        $data = $this->validate([
            'title'          => 'required|string|max:200',
            'description'    => 'required|string|max:2000',
            'vendor'         => 'nullable|string|max:200',
            'category'       => 'required|in:supplies,equipment,services,software,other',
            'priority'       => 'required|in:low,medium,high,urgent',
            'estimated_cost' => 'required|numeric|min:0.01',
            'account_id'     => 'nullable|exists:accounts,id',
            'required_by'    => 'nullable|date',
        ]);

        $status = in_array($submitStatus, ['draft', 'pending']) ? $submitStatus : 'draft';

        if ($this->editing && $this->editingId) {
            $req = ProcurementRequest::findOrFail($this->editingId);
            $req->update(array_merge($data, ['status' => $status, 'required_by' => $data['required_by'] ?: null]));
            AuditLog::record('update', "Updated procurement {$req->reference}", $req);
            session()->flash('message', $status === 'pending' ? 'Request submitted for approval.' : 'Draft saved.');
        } else {
            $req = ProcurementRequest::create(array_merge($data, [
                'reference'    => ProcurementRequest::generateReference(),
                'requested_by' => auth()->id(),
                'status'       => $status,
                'required_by'  => $data['required_by'] ?: null,
            ]));
            AuditLog::record('create', "Created procurement {$req->reference}: {$req->title}", $req);
            session()->flash('message', $status === 'pending' ? 'Request submitted for approval.' : 'Draft saved.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function cancel(int $id): void
    {
        $req = ProcurementRequest::findOrFail($id);

        if ($req->requested_by !== auth()->id() && !auth()->user()->isHRAdmin()) {
            session()->flash('error', 'You can only cancel your own requests.');
            return;
        }

        if (!in_array($req->status, ['draft', 'pending'])) {
            session()->flash('error', 'Only draft or pending requests can be cancelled.');
            return;
        }

        $req->update(['status' => 'cancelled']);
        AuditLog::record('update', "Cancelled procurement {$req->reference}", $req);
        session()->flash('message', 'Request cancelled.');
    }

    // ── Review ────────────────────────────────────────────────────────────────

    public function openReview(int $id, string $action): void
    {
        $this->authorize('approve procurement');
        $this->reviewingId = $id;
        $this->review_action = $action;
        $req = ProcurementRequest::findOrFail($id);
        $this->approved_amount = (string) $req->estimated_cost;
        $this->review_notes = '';
        $this->showReviewModal = true;
    }

    public function submitReview(): void
    {
        $this->authorize('approve procurement');

        $this->validate([
            'approved_amount' => $this->review_action === 'approve' ? 'required|numeric|min:0.01' : 'nullable',
            'review_notes'    => $this->review_action === 'reject' ? 'required|string|max:500' : 'nullable|string|max:500',
        ]);

        $req = ProcurementRequest::with('account')->findOrFail($this->reviewingId);

        try {
            $action = app(ApproveProcurement::class);

            if ($this->review_action === 'approve') {
                $action->approve($req, auth()->user(), (float) $this->approved_amount, $this->review_notes ?: null);
                session()->flash('message', 'Request approved and account debited.');
            } else {
                $action->reject($req, auth()->user(), $this->review_notes ?: null);
                session()->flash('message', 'Request rejected.');
            }
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showReviewModal = false;
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'vendor', 'required_by', 'prq_notes', 'estimated_cost', 'account_id']);
        $this->category = 'supplies';
        $this->priority = 'medium';
    }

    public function render()
    {
        $user = auth()->user();
        $canReview = $user->can('approve procurement');

        $query = ProcurementRequest::with(['requester:id,name', 'account:id,name,code', 'reviewer:id,name'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%")
                  ->orWhere('vendor', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->unless($canReview, fn($q) => $q->where('requested_by', $user->id))
            ->orderByDesc('created_at');

        $accounts = Account::where('active', true)->orderBy('name')->get(['id', 'name', 'code', 'balance']);

        return view('livewire.accounts.procurement', compact('query', 'accounts', 'canReview'));
    }
}
