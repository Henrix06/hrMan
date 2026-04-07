<?php

namespace App\Livewire\Accounts;

use App\Actions\Accounts\ReviewClaim;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\OvertimeClaim;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class OvertimeClaimList extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $search = '';

    // Form modal
    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public string $work_date = '';
    public string $start_time = '';
    public string $end_time = '';
    public string $hourly_rate = '';
    public string $multiplier = '1.5';
    public string $reason = '';
    public ?int $account_id = null;
    public float $computed_hours = 0;
    public float $computed_amount = 0;

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
        if (in_array($prop, ['start_time', 'end_time', 'hourly_rate', 'multiplier'])) {
            $this->recalculate();
        }
    }

    private function recalculate(): void
    {
        if ($this->start_time && $this->end_time) {
            try {
                $start = \Carbon\Carbon::parse($this->start_time);
                $end   = \Carbon\Carbon::parse($this->end_time);
                if ($end->gt($start)) {
                    $this->computed_hours  = round($end->diffInMinutes($start) / 60, 2);
                    $rate       = (float) ($this->hourly_rate ?: 0);
                    $multiplier = (float) ($this->multiplier ?: 1.5);
                    $this->computed_amount = round($this->computed_hours * $rate * $multiplier, 2);
                    return;
                }
            } catch (\Throwable) {}
        }
        $this->computed_hours  = 0;
        $this->computed_amount = 0;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editing = false;
        // Pre-fill hourly rate from user's salary if available
        $user = auth()->user();
        if ($user->base_salary) {
            $this->hourly_rate = (string) round((float) $user->base_salary / (22 * 8), 2);
        }
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $claim = OvertimeClaim::findOrFail($id);

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
        $this->work_date = $claim->work_date->format('Y-m-d');
        $this->start_time = $claim->start_time;
        $this->end_time = $claim->end_time;
        $this->hourly_rate = (string) $claim->hourly_rate;
        $this->multiplier = (string) $claim->multiplier;
        $this->reason = $claim->reason;
        $this->account_id = $claim->account_id;
        $this->recalculate();
        $this->showModal = true;
    }

    public function save(string $submitStatus = 'draft'): void
    {
        $this->validate([
            'work_date'   => 'required|date',
            'start_time'  => 'required',
            'end_time'    => 'required|after:start_time',
            'hourly_rate' => 'required|numeric|min:0.01',
            'multiplier'  => 'required|numeric|min:1|max:3',
            'reason'      => 'required|string|max:1000',
            'account_id'  => 'nullable|exists:accounts,id',
        ]);

        $this->recalculate();

        if ($this->computed_hours <= 0) {
            $this->addError('end_time', 'End time must be after start time.');
            return;
        }

        $status = in_array($submitStatus, ['draft', 'pending']) ? $submitStatus : 'draft';

        $payload = [
            'work_date'    => $this->work_date,
            'start_time'   => $this->start_time,
            'end_time'     => $this->end_time,
            'hours'        => $this->computed_hours,
            'hourly_rate'  => (float) $this->hourly_rate,
            'multiplier'   => (float) $this->multiplier,
            'claim_amount' => $this->computed_amount,
            'reason'       => $this->reason,
            'account_id'   => $this->account_id,
            'status'       => $status,
        ];

        if ($this->editing && $this->editingId) {
            $claim = OvertimeClaim::findOrFail($this->editingId);
            $claim->update($payload);
            AuditLog::record('update', "Updated overtime claim {$claim->reference}", $claim);
        } else {
            $claim = OvertimeClaim::create(array_merge($payload, [
                'reference' => OvertimeClaim::generateReference(),
                'user_id'   => auth()->id(),
            ]));
            AuditLog::record('create', "Submitted overtime claim {$claim->reference}", $claim);
        }

        session()->flash('message', $status === 'pending' ? 'Claim submitted for approval.' : 'Draft saved.');
        $this->showModal = false;
        $this->resetPage();
    }

    public function cancel(int $id): void
    {
        $claim = OvertimeClaim::findOrFail($id);

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
        $claim = OvertimeClaim::findOrFail($id);
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

        $claim = OvertimeClaim::with('account', 'employee')->findOrFail($this->reviewingId);

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
        $this->reset(['editingId', 'work_date', 'start_time', 'end_time', 'hourly_rate', 'reason', 'account_id']);
        $this->multiplier = '1.5';
        $this->computed_hours = 0;
        $this->computed_amount = 0;
    }

    public function render()
    {
        $user = auth()->user();
        $canReview = $user->can('approve claims');

        $claims = OvertimeClaim::with(['employee:id,name', 'account:id,name,code', 'reviewer:id,name'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhereHas('employee', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->unless($canReview, fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->paginate(15);

        $accounts = Account::where('active', true)->orderBy('name')->get(['id', 'name', 'code', 'balance']);

        return view('livewire.accounts.overtime', compact('claims', 'accounts', 'canReview'));
    }
}
