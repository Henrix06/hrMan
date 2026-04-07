<?php

namespace App\Livewire\Announcements;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementList extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $body = '';
    public string $audience = 'all';
    public bool $pinned = false;
    public bool $publish_now = true;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'body' => 'required|string|max:5000',
            'audience' => 'required|in:all,managers,employees',
            'pinned' => 'boolean',
            'publish_now' => 'boolean',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['title', 'body', 'editingId', 'editing']);
        $this->audience = 'all';
        $this->pinned = false;
        $this->publish_now = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $ann = Announcement::findOrFail($id);
        $this->editingId = $id;
        $this->editing = true;
        $this->title = $ann->title;
        $this->body = $ann->body;
        $this->audience = $ann->audience;
        $this->pinned = $ann->pinned;
        $this->publish_now = $ann->published_at !== null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('manage settings');
        $data = $this->validate();
        $data['published_at'] = $data['publish_now'] ? now() : null;
        unset($data['publish_now']);
        $data['created_by'] = auth()->id();

        if ($this->editing && $this->editingId) {
            $ann = Announcement::findOrFail($this->editingId);
            $ann->update($data);
            AuditLog::record('update', "Updated announcement: {$ann->title}", $ann);
            session()->flash('message', 'Announcement updated.');
        } else {
            $ann = Announcement::create($data);
            AuditLog::record('create', "Created announcement: {$ann->title}", $ann);

            if ($ann->published_at) {
                $this->broadcastNotifications($ann);
            }
            session()->flash('message', 'Announcement published.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    private function broadcastNotifications(Announcement $ann): void
    {
        $query = User::query();
        if ($ann->audience === 'managers') {
            $query->whereHas('roles', fn($q) => $q->whereIn('name', ['Manager', 'HR Admin', 'Super Admin']));
        } elseif ($ann->audience === 'employees') {
            $query->whereHas('roles', fn($q) => $q->where('name', 'Employee'));
        }

        $query->where('id', '!=', auth()->id())->each(function (User $user) use ($ann) {
            HrNotification::notify($user->id, 'announcement', $ann->title,
                substr(strip_tags($ann->body), 0, 120) . '...', route('announcements.index'));
        });
    }

    public function delete(int $id): void
    {
        $this->authorize('manage settings');
        $ann = Announcement::findOrFail($id);
        AuditLog::record('delete', "Deleted announcement: {$ann->title}", $ann);
        $ann->delete();
        session()->flash('message', 'Announcement deleted.');
    }

    public function render()
    {
        $canManage = auth()->user()->can('manage settings');

        $announcements = Announcement::with('author')
            ->when(!$canManage, fn($q) => $q->published())
            ->orderByDesc('pinned')
            ->orderByDesc('published_at')
            ->paginate(10);

        return view('livewire.announcements.index', compact('announcements', 'canManage'));
    }
}
