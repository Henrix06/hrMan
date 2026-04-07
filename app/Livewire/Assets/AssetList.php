<?php

namespace App\Livewire\Assets;

use App\Actions\Assets\AssignAsset;
use App\Actions\Assets\ReturnAsset;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AuditLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class AssetList extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';

    // Asset create/edit modal
    public bool $showAssetModal = false;
    public bool $editingAsset = false;
    public ?int $editingAssetId = null;

    public string $name = '';
    public string $category = '';
    public string $brand = '';
    public string $model = '';
    public string $serial_number = '';
    public string $purchase_date = '';
    public string $purchase_cost = '';
    public string $condition = 'good';
    public string $asset_notes = '';

    // Assign modal
    public bool $showAssignModal = false;
    public ?int $assignAssetId = null;
    public ?int $assign_user_id = null;
    public string $assigned_date = '';
    public string $expected_return_date = '';
    public string $assign_notes = '';

    // Return modal
    public bool $showReturnModal = false;
    public ?int $returnAssignmentId = null;
    public string $return_date = '';
    public string $condition_on_return = 'good';
    public string $return_notes = '';

    // History modal
    public bool $showHistoryModal = false;
    public ?Asset $historyAsset = null;

    protected $queryString = [
        'search'         => ['except' => ''],
        'statusFilter'   => ['except' => ''],
        'categoryFilter' => ['except' => ''],
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'categoryFilter'])) {
            $this->resetPage();
        }
    }

    // ── Asset CRUD ────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->authorize('manage assets');
        $this->resetAssetForm();
        $this->editingAsset = false;
        $this->showAssetModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('manage assets');
        $asset = Asset::findOrFail($id);

        if ($asset->isAssigned()) {
            session()->flash('error', 'Cannot edit an asset that is currently assigned.');
            return;
        }

        $this->editingAssetId = $id;
        $this->editingAsset = true;
        $this->name = $asset->name;
        $this->category = $asset->category;
        $this->brand = $asset->brand ?? '';
        $this->model = $asset->model ?? '';
        $this->serial_number = $asset->serial_number ?? '';
        $this->purchase_date = $asset->purchase_date?->format('Y-m-d') ?? '';
        $this->purchase_cost = (string) ($asset->purchase_cost ?? '');
        $this->condition = $asset->condition;
        $this->asset_notes = $asset->notes ?? '';
        $this->showAssetModal = true;
    }

    public function saveAsset(): void
    {
        $this->authorize('manage assets');

        $data = $this->validate([
            'name'          => 'required|string|max:200',
            'category'      => 'required|string|max:100',
            'brand'         => 'nullable|string|max:100',
            'model'         => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100|unique:assets,serial_number' . ($this->editingAssetId ? ",{$this->editingAssetId}" : ''),
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'condition'     => 'required|in:new,good,fair,poor,damaged',
            'asset_notes'   => 'nullable|string|max:1000',
        ]);

        if ($this->editingAsset && $this->editingAssetId) {
            $asset = Asset::findOrFail($this->editingAssetId);
            $old = $asset->toArray();
            $asset->update([
                'name'          => $data['name'],
                'category'      => $data['category'],
                'brand'         => $data['brand'] ?: null,
                'model'         => $data['model'] ?: null,
                'serial_number' => $data['serial_number'] ?: null,
                'purchase_date' => $data['purchase_date'] ?: null,
                'purchase_cost' => $data['purchase_cost'] ?: null,
                'condition'     => $data['condition'],
                'notes'         => $data['asset_notes'] ?: null,
            ]);
            AuditLog::record('update', "Updated asset [{$asset->asset_tag}] {$asset->name}", $asset, $old, $asset->fresh()->toArray());
            session()->flash('message', 'Asset updated.');
        } else {
            $asset = Asset::create([
                'asset_tag'     => Asset::generateAssetTag(),
                'name'          => $data['name'],
                'category'      => $data['category'],
                'brand'         => $data['brand'] ?: null,
                'model'         => $data['model'] ?: null,
                'serial_number' => $data['serial_number'] ?: null,
                'purchase_date' => $data['purchase_date'] ?: null,
                'purchase_cost' => $data['purchase_cost'] ?: null,
                'condition'     => $data['condition'],
                'status'        => 'available',
                'notes'         => $data['asset_notes'] ?: null,
                'created_by'    => auth()->id(),
            ]);
            AuditLog::record('create', "Created asset [{$asset->asset_tag}] {$asset->name}", $asset);
            session()->flash('message', 'Asset created.');
        }

        $this->showAssetModal = false;
        $this->resetPage();
    }

    public function retireAsset(int $id): void
    {
        $this->authorize('manage assets');
        $asset = Asset::findOrFail($id);

        if ($asset->isAssigned()) {
            session()->flash('error', 'Cannot retire an asset that is currently assigned.');
            return;
        }

        $old = ['status' => $asset->status];
        $asset->update(['status' => 'retired']);
        AuditLog::record('update', "Retired asset [{$asset->asset_tag}] {$asset->name}", $asset, $old, ['status' => 'retired']);
        session()->flash('message', 'Asset retired.');
    }

    // ── Assignment ────────────────────────────────────────────────────────────

    public function openAssign(int $assetId): void
    {
        $this->authorize('assign assets');
        $asset = Asset::findOrFail($assetId);

        if (!$asset->isAvailable()) {
            session()->flash('error', 'This asset is not available for assignment.');
            return;
        }

        $this->assignAssetId = $assetId;
        $this->assign_user_id = null;
        $this->assigned_date = now()->toDateString();
        $this->expected_return_date = '';
        $this->assign_notes = '';
        $this->showAssignModal = true;
    }

    public function saveAssignment(): void
    {
        $this->authorize('assign assets');

        $this->validate([
            'assign_user_id'       => 'required|exists:users,id',
            'assigned_date'        => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:assigned_date',
            'assign_notes'         => 'nullable|string|max:1000',
        ]);

        $asset = Asset::findOrFail($this->assignAssetId);
        $employee = User::findOrFail($this->assign_user_id);

        try {
            app(AssignAsset::class)->execute(
                $asset,
                $employee,
                auth()->user(),
                $this->assigned_date,
                $this->expected_return_date ?: null,
                $this->assign_notes ?: null,
            );
            session()->flash('message', "Asset assigned to {$employee->name}.");
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showAssignModal = false;
        $this->resetPage();
    }

    // ── Return ────────────────────────────────────────────────────────────────

    public function openReturn(int $assignmentId): void
    {
        $this->authorize('assign assets');
        $this->returnAssignmentId = $assignmentId;
        $this->return_date = now()->toDateString();
        $this->condition_on_return = 'good';
        $this->return_notes = '';
        $this->showReturnModal = true;
    }

    public function saveReturn(): void
    {
        $this->authorize('assign assets');

        $this->validate([
            'return_date'         => 'required|date',
            'condition_on_return' => 'required|in:good,fair,poor,damaged',
            'return_notes'        => 'nullable|string|max:1000',
        ]);

        $assignment = AssetAssignment::with('asset', 'employee')->findOrFail($this->returnAssignmentId);

        try {
            app(ReturnAsset::class)->execute(
                $assignment,
                auth()->user(),
                $this->return_date,
                $this->condition_on_return,
                $this->return_notes ?: null,
            );
            session()->flash('message', 'Asset return recorded.');
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showReturnModal = false;
        $this->resetPage();
    }

    // ── History ───────────────────────────────────────────────────────────────

    public function viewHistory(int $assetId): void
    {
        $this->historyAsset = Asset::with([
            'assignments' => fn($q) => $q->with(['employee:id,name', 'assignedBy:id,name', 'returnedTo:id,name'])->latest(),
        ])->findOrFail($assetId);
        $this->showHistoryModal = true;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resetAssetForm(): void
    {
        $this->reset([
            'editingAssetId', 'name', 'category', 'brand', 'model',
            'serial_number', 'purchase_date', 'purchase_cost', 'asset_notes',
        ]);
        $this->condition = 'good';
    }

    public function render()
    {
        $this->authorize('view assets');

        $assets = Asset::with(['activeAssignment.employee:id,name'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('asset_tag', 'like', "%{$this->search}%")
                  ->orWhere('serial_number', 'like', "%{$this->search}%")
                  ->orWhere('brand', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $categories = \Illuminate\Support\Facades\Cache::remember('asset_categories', 120,
            fn() => Asset::distinct()->orderBy('category')->pluck('category')
        );

        $employees = User::where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_id', 'position']);

        // Active assignments for the return modal context
        $activeAssignments = AssetAssignment::with(['asset:id,asset_tag,name', 'employee:id,name'])
            ->where('status', 'active')
            ->orderByDesc('assigned_date')
            ->get();

        return view('livewire.assets.index', compact('assets', 'categories', 'employees', 'activeAssignments'));
    }
}
