<?php

namespace App\Livewire\Folders;

use App\Models\Folder;
use App\Models\FolderDocument;
use App\Models\FolderShare;
use App\Models\HrNotification;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class FolderManager extends Component
{
    // ── View state: 'folders' | 'browser' | 'editor' ─────────────────────────
    public string $view = 'folders';

    // ── Root-level modals ──────────────────────────────────────────────────────
    public bool $showCreateModal   = false;
    public bool $showShareModal    = false;
    public bool $showDeleteConfirm = false;

    // ── Create folder form (root + subfolder) ─────────────────────────────────
    public string $name                 = '';
    public string $description          = '';
    public bool   $isEncrypted          = false;
    public string $encryptionPassphrase = '';
    public bool   $isPublic             = false;
    public ?int   $createParentId       = null;

    // ── Share modal ───────────────────────────────────────────────────────────
    public ?int   $sharingFolderId   = null;
    public string $shareSearch       = '';
    public array  $selectedUsers     = [];
    public string $defaultPermission = 'view';

    // ── Delete ────────────────────────────────────────────────────────────────
    public ?int $deletingFolderId = null;

    // ── Encryption unlock ─────────────────────────────────────────────────────
    public array $unlockedFolders  = [];
    public array $unlockPassphrase = [];
    public array $unlockErrors     = [];

    // ── Folder browser ────────────────────────────────────────────────────────
    public ?int  $browseFolderId = null;
    public array $breadcrumbs    = [];

    // ── Document editor ───────────────────────────────────────────────────────
    public ?int   $editingDocId = null;
    public string $docTitle     = '';
    public string $docContent   = '';
    public string $docType      = 'document';
    public int    $ssRows       = 20;
    public int    $ssCols       = 10;
    public array  $ssCells      = [];

    // ── Delete document / subfolder ───────────────────────────────────────────
    public bool $showDeleteDocConfirm = false;
    public ?int $deletingDocId        = null;
    public bool $showDeleteSubConfirm = false;
    public ?int $deletingSubId        = null;

    // ─────────────────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        return [
            'name'                 => 'required|string|max:100',
            'description'          => 'nullable|string|max:500',
            'isEncrypted'          => 'boolean',
            'encryptionPassphrase' => 'required_if:isEncrypted,true|nullable|string|min:4',
            'isPublic'             => 'boolean',
        ];
    }

    // ── Root folder creation ──────────────────────────────────────────────────

    public function openCreate(?int $parentId = null): void
    {
        $this->resetCreateForm();
        $this->createParentId  = $parentId;
        $this->showCreateModal = true;
    }

    public function createFolder(): void
    {
        $this->validate();

        $folder = Folder::create([
            'owner_id'       => auth()->id(),
            'parent_id'      => $this->createParentId,
            'name'           => $this->name,
            'description'    => $this->description ?: null,
            'is_encrypted'   => $this->isEncrypted,
            'encryption_key' => $this->isEncrypted ? Hash::make($this->encryptionPassphrase) : null,
            'is_public'      => $this->isPublic,
        ]);

        $this->showCreateModal = false;
        $this->resetCreateForm();

        if ($this->browseFolderId !== null || $this->createParentId !== null) {
            session()->flash('browserMessage', "Folder \"{$folder->name}\" created.");
        } else {
            session()->flash('message', "Folder \"{$folder->name}\" created.");
        }
    }

    // ── Folder browser ────────────────────────────────────────────────────────

    public function openFolder(int $folderId): void
    {
        $folder = Folder::findOrFail($folderId);
        if (!$folder->isAccessibleBy(auth()->id())) return;
        if ($folder->is_encrypted && !in_array($folderId, $this->unlockedFolders)) return;

        $this->browseFolderId = $folderId;
        $this->buildBreadcrumbs($folderId);
        $this->view = 'browser';
    }

    public function navigateTo(int $folderId): void
    {
        $this->browseFolderId = $folderId;
        $this->buildBreadcrumbs($folderId);
        $this->view = 'browser';
    }

    public function navigateUp(): void
    {
        if (count($this->breadcrumbs) <= 1) {
            $this->goHome();
            return;
        }
        $parent = $this->breadcrumbs[count($this->breadcrumbs) - 2];
        $this->browseFolderId = $parent['id'];
        $this->buildBreadcrumbs($parent['id']);
        $this->view = 'browser';
    }

    public function goHome(): void
    {
        $this->view           = 'folders';
        $this->browseFolderId = null;
        $this->breadcrumbs    = [];
    }

    // kept for backwards compat — just go home
    public function closeBrowser(): void
    {
        $this->goHome();
    }

    private function buildBreadcrumbs(int $folderId): void
    {
        $crumbs = [];
        $current = Folder::find($folderId);
        while ($current) {
            array_unshift($crumbs, ['id' => $current->id, 'name' => $current->name]);
            $current = $current->parent_id ? Folder::find($current->parent_id) : null;
        }
        $this->breadcrumbs = $crumbs;
    }

    // ── Document CRUD ─────────────────────────────────────────────────────────

    public function openNewDoc(): void
    {
        $this->editingDocId = null;
        $this->docTitle     = '';
        $this->docContent   = '';
        $this->docType      = 'document';
        $this->ssCells      = [];
        $this->ssRows       = 20;
        $this->ssCols       = 10;
        $this->view         = 'editor';
    }

    public function openEditDoc(int $docId): void
    {
        $doc = FolderDocument::findOrFail($docId);
        $this->editingDocId = $doc->id;
        $this->docTitle     = $doc->title;
        $this->docContent   = $doc->content ?? '';
        $this->docType      = $doc->type;
        $this->ssCells      = $doc->meta['cells'] ?? [];
        $this->ssRows       = max(20, count($doc->meta['cells'] ?? []));
        $this->ssCols       = 10;
        $this->view         = 'editor';
    }

    public function closeEditor(): void
    {
        $this->view = 'browser';
    }

    public function saveDoc(): void
    {
        $this->validate([
            'docTitle'   => 'required|string|max:200',
            'docContent' => 'nullable|string',
            'docType'    => 'in:document,note,docx,spreadsheet',
        ]);

        if (!$this->browseFolderId) return;

        $folder = Folder::findOrFail($this->browseFolderId);
        if (!$this->canEdit($folder)) {
            session()->flash('browserMessage', 'You do not have edit permission for this folder.');
            $this->view = 'browser';
            return;
        }

        $meta = $this->docType === 'spreadsheet' ? ['cells' => $this->ssCells] : null;

        $data = [
            'title'   => $this->docTitle,
            'content' => in_array($this->docType, ['document', 'note', 'docx']) ? $this->docContent : null,
            'type'    => $this->docType,
            'meta'    => $meta,
        ];

        if ($this->editingDocId) {
            FolderDocument::where('id', $this->editingDocId)
                ->where('folder_id', $this->browseFolderId)
                ->update($data);
        } else {
            FolderDocument::create(array_merge($data, [
                'folder_id'  => $this->browseFolderId,
                'created_by' => auth()->id(),
            ]));
        }

        $this->view = 'browser';
        session()->flash('browserMessage', $this->editingDocId ? 'Document updated.' : 'Document created.');
    }

    // Called from Alpine to sync spreadsheet cell data back to Livewire before save
    public function syncCells(array $cells): void
    {
        $this->ssCells = $cells;
    }

    public function confirmDeleteDoc(int $docId): void
    {
        $this->deletingDocId        = $docId;
        $this->showDeleteDocConfirm = true;
    }

    public function deleteDoc(): void
    {
        if (!$this->deletingDocId || !$this->browseFolderId) return;

        $folder = Folder::findOrFail($this->browseFolderId);
        if (!$this->canEdit($folder)) return;

        FolderDocument::where('id', $this->deletingDocId)
            ->where('folder_id', $this->browseFolderId)
            ->delete();

        $this->showDeleteDocConfirm = false;
        $this->deletingDocId        = null;
        session()->flash('browserMessage', 'Document deleted.');
    }

    // ── Subfolder delete ──────────────────────────────────────────────────────

    public function confirmDeleteSub(int $subId): void
    {
        $this->deletingSubId        = $subId;
        $this->showDeleteSubConfirm = true;
    }

    public function deleteSub(): void
    {
        if (!$this->deletingSubId) return;

        Folder::where('id', $this->deletingSubId)
            ->where('owner_id', auth()->id())
            ->delete();

        $this->showDeleteSubConfirm = false;
        $this->deletingSubId        = null;
        session()->flash('browserMessage', 'Subfolder deleted.');
    }

    // ── Permission helper ─────────────────────────────────────────────────────

    private function canEdit(Folder $folder): bool
    {
        if ($folder->owner_id === auth()->id()) return true;
        $share = $folder->shares()->where('user_id', auth()->id())->first();
        return $share && $share->permission === 'edit';
    }

    // ── Share modal ───────────────────────────────────────────────────────────

    public function openShare(int $folderId): void
    {
        $folder = Folder::where('id', $folderId)->where('owner_id', auth()->id())->firstOrFail();
        $this->sharingFolderId = $folder->id;
        $this->shareSearch     = '';
        $this->selectedUsers   = [];

        foreach ($folder->shares as $share) {
            $this->selectedUsers[$share->user_id] = $share->permission;
        }

        $this->showShareModal = true;
    }

    public function toggleUserShare(int $userId): void
    {
        if (isset($this->selectedUsers[$userId])) {
            unset($this->selectedUsers[$userId]);
        } else {
            $this->selectedUsers[$userId] = $this->defaultPermission;
        }
    }

    public function setPermission(int $userId, string $permission): void
    {
        if (isset($this->selectedUsers[$userId])) {
            $this->selectedUsers[$userId] = $permission;
        }
    }

    public function saveShares(): void
    {
        if (!$this->sharingFolderId) return;

        $folder = Folder::where('id', $this->sharingFolderId)
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        $existingShares = $folder->shares()->pluck('user_id')->toArray();

        $toRemove = array_diff($existingShares, array_keys($this->selectedUsers));
        if ($toRemove) {
            FolderShare::where('folder_id', $folder->id)->whereIn('user_id', $toRemove)->delete();
        }

        foreach ($this->selectedUsers as $userId => $permission) {
            FolderShare::updateOrCreate(
                ['folder_id' => $folder->id, 'user_id' => $userId],
                ['permission' => $permission]
            );
            if (!in_array($userId, $existingShares)) {
                $this->notifyUser((int) $userId, $folder);
            }
        }

        $this->showShareModal = false;
        $this->sharingFolderId = null;
        session()->flash('message', 'Folder sharing updated.');
    }

    private function notifyUser(int $userId, Folder $folder): void
    {
        $sharer = auth()->user()->name;

        HrNotification::notify(
            $userId, 'folder_shared',
            'Folder Shared With You',
            "{$sharer} shared the folder \"{$folder->name}\" with you.",
            route('folders.index')
        );

        $conv = ChatConversation::between(auth()->id(), $userId);
        ChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_id'       => auth()->id(),
            'body'            => "Hi! I've shared the folder \"{$folder->name}\" with you. You can find it in the Folders section.",
        ]);
        $conv->update(['last_message_at' => now()]);
    }

    // ── Toggle public ─────────────────────────────────────────────────────────

    public function togglePublic(int $folderId): void
    {
        $folder = Folder::where('id', $folderId)->where('owner_id', auth()->id())->firstOrFail();
        $folder->update(['is_public' => !$folder->is_public]);
        session()->flash('message', $folder->is_public ? 'Folder is now public.' : 'Folder is now private.');
    }

    // ── Root folder delete ────────────────────────────────────────────────────

    public function confirmDelete(int $folderId): void
    {
        $this->deletingFolderId  = $folderId;
        $this->showDeleteConfirm = true;
    }

    public function deleteFolder(): void
    {
        if (!$this->deletingFolderId) return;

        Folder::where('id', $this->deletingFolderId)
            ->where('owner_id', auth()->id())
            ->delete();

        $this->showDeleteConfirm = false;
        $this->deletingFolderId  = null;
        session()->flash('message', 'Folder deleted.');
    }

    // ── Encryption unlock ─────────────────────────────────────────────────────

    public function tryUnlock(int $folderId): void
    {
        $folder     = Folder::findOrFail($folderId);
        $passphrase = $this->unlockPassphrase[$folderId] ?? '';

        if (Hash::check($passphrase, $folder->encryption_key)) {
            $this->unlockedFolders[] = $folderId;
            unset($this->unlockErrors[$folderId], $this->unlockPassphrase[$folderId]);
        } else {
            $this->unlockErrors[$folderId] = 'Incorrect passphrase.';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resetCreateForm(): void
    {
        $this->name = $this->description = $this->encryptionPassphrase = '';
        $this->isEncrypted = $this->isPublic = false;
        $this->createParentId = null;
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $me = auth()->id();

        $myFolders = Folder::with(['shares.user'])
            ->where('owner_id', $me)
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->get();

        $sharedWithMe = Folder::with('owner')
            ->where('owner_id', '!=', $me)
            ->whereNull('parent_id')
            ->where(function ($q) use ($me) {
                $q->where('is_public', true)
                  ->orWhereHas('shares', fn($s) => $s->where('user_id', $me));
            })
            ->orderByDesc('created_at')
            ->get();

        // People search for share modal
        $searchResults = collect();
        if (strlen($this->shareSearch) >= 1) {
            $searchResults = User::where('id', '!=', $me)
                ->where('name', 'like', "%{$this->shareSearch}%")
                ->orderBy('name')->limit(10)
                ->get(['id', 'name', 'position']);
        }

        // Browser contents
        $browserFolder    = null;
        $browserSubfolders = collect();
        $browserDocuments  = collect();

        if ($this->browseFolderId) {
            $browserFolder = Folder::with(['owner', 'shares'])->find($this->browseFolderId);
            if ($browserFolder) {
                $browserSubfolders = Folder::where('parent_id', $this->browseFolderId)
                    ->orderBy('name')->get();
                $browserDocuments = FolderDocument::with('creator')
                    ->where('folder_id', $this->browseFolderId)
                    ->orderByDesc('updated_at')->get();
            }
        }

        return view('livewire.folders.folder-manager', compact(
            'myFolders', 'sharedWithMe', 'searchResults',
            'browserFolder', 'browserSubfolders', 'browserDocuments'
        ));
    }
}
