<div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SHARED PAGE HEADER
════════════════════════════════════════════════════════════════════════════ --}}
<div class="flex items-center gap-3 mb-6">
    @if($view === 'browser' || $view === 'editor')
    <button wire:click="goHome" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-primary hover:bg-primary/5 transition-colors flex-shrink-0" title="All Folders">
        <span class="material-symbols-outlined text-sm">home</span>
    </button>
    @endif
    @if($view === 'editor' && $browseFolderId)
    <button wire:click="closeEditor" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-primary hover:bg-primary/5 transition-colors flex-shrink-0" title="Back to folder">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
    </button>
    @endif
    @if($view === 'browser')
    <button wire:click="navigateUp" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-primary hover:bg-primary/5 transition-colors flex-shrink-0" title="Up one level">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
    </button>
    @endif

    <div class="flex items-center gap-1.5 flex-1 min-w-0 overflow-x-auto">
        @if($view === 'folders')
            <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 font-headline">Folders</h2>
        @else
            <button wire:click="goHome" class="text-sm text-slate-400 hover:text-primary transition-colors flex-shrink-0 font-headline">Folders</button>
            @foreach($breadcrumbs as $i => $crumb)
            <span class="text-slate-300 dark:text-slate-600 flex-shrink-0">/</span>
            @if($i === count($breadcrumbs) - 1 && $view === 'browser')
                <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-headline truncate">{{ $crumb['name'] }}</span>
            @else
                <button wire:click="navigateTo({{ $crumb['id'] }})"
                    class="text-sm {{ $view === 'editor' ? 'text-slate-400 hover:text-primary' : 'text-primary hover:underline' }} transition-colors flex-shrink-0 font-headline truncate">{{ $crumb['name'] }}</button>
            @endif
            @endforeach
            @if($view === 'editor')
            <span class="text-slate-300 dark:text-slate-600 flex-shrink-0">/</span>
            <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-headline truncate">{{ $editingDocId ? $docTitle : 'New Document' }}</span>
            @endif
        @endif
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
        @if($view === 'folders')
        <button wire:click="openCreate()" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2 rounded-xl text-sm font-bold hover:opacity-90 transition-opacity shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined text-sm">create_new_folder</span> New Folder
        </button>
        @elseif($view === 'browser')
        @php $canEditBrowser = $browserFolder && ($browserFolder->owner_id === auth()->id() || ($browserFolder->shares->firstWhere('user_id', auth()->id())?->permission === 'edit')); @endphp
        @if($canEditBrowser)
        <button wire:click="openCreate({{ $browseFolderId }})" class="flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-primary hover:bg-primary/5 px-3 py-2 rounded-xl transition-colors border border-slate-200 dark:border-white/10">
            <span class="material-symbols-outlined text-sm">create_new_folder</span> New Folder
        </button>
        <button wire:click="openNewDoc" class="flex items-center gap-1.5 text-xs font-bold bg-primary text-on-primary px-3 py-2 rounded-xl hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-sm">note_add</span> New Document
        </button>
        @endif
        @endif
    </div>
</div>

@if(session()->has('message'))
<div class="mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
@endif
@if(session()->has('browserMessage'))
<div class="mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('browserMessage') }}</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     VIEW: FOLDERS
════════════════════════════════════════════════════════════════════════════ --}}
@if($view === 'folders')
<section class="mb-10">
    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-4">My Folders</h3>
    @if($myFolders->isEmpty())
    <div class="glass-card rounded-2xl p-12 text-center text-slate-400">
        <span class="material-symbols-outlined text-5xl block mb-3">folder_open</span>
        <p>No folders yet. Create your first one.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($myFolders as $folder)
        @php $isUnlocked = in_array($folder->id, $unlockedFolders); @endphp
        <div class="glass-card rounded-2xl p-5 flex flex-col gap-3 group">
            <button wire:click="openFolder({{ $folder->id }})" @if($folder->is_encrypted && !$isUnlocked) disabled @endif
                class="flex items-start gap-3 text-left w-full disabled:opacity-60 disabled:cursor-not-allowed">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 {{ $folder->is_encrypted ? 'bg-amber-100 text-amber-600' : 'bg-primary/10 text-primary' }}">
                    <span class="material-symbols-outlined">{{ $folder->is_encrypted && !$isUnlocked ? 'lock' : 'folder' }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-800 dark:text-slate-100 truncate font-headline">{{ $folder->name }}</p>
                    @if($folder->description)<p class="text-xs text-slate-400 truncate mt-0.5">{{ $folder->description }}</p>@endif
                </div>
            </button>
            <div class="flex flex-wrap gap-1.5">
                @if($folder->is_public)<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Public</span>
                @else<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">Private</span>@endif
                @if($folder->is_encrypted)<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $isUnlocked ? 'Unlocked' : 'Encrypted' }}</span>@endif
                @if($folder->shares->count() > 0)<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ $folder->shares->count() }} {{ $folder->shares->count() === 1 ? 'member' : 'members' }}</span>@endif
            </div>
            @if($folder->is_encrypted && !$isUnlocked)
            <div class="flex gap-2">
                <input wire:model="unlockPassphrase.{{ $folder->id }}" type="password" placeholder="Passphrase to open" class="flex-1 px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                <button wire:click="tryUnlock({{ $folder->id }})" class="px-3 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-lg hover:opacity-90">Unlock</button>
            </div>
            @if(isset($unlockErrors[$folder->id]))<p class="text-xs text-error -mt-1">{{ $unlockErrors[$folder->id] }}</p>@endif
            @endif
            @if($folder->shares->count() > 0)
            <div class="flex -space-x-2">
                @foreach($folder->shares->take(5) as $share)
                <div class="w-6 h-6 rounded-full bg-primary/20 border-2 border-white dark:border-slate-800 flex items-center justify-center text-primary text-[9px] font-bold" title="{{ $share->user->name }}">{{ strtoupper(substr($share->user->name, 0, 2)) }}</div>
                @endforeach
                @if($folder->shares->count() > 5)<div class="w-6 h-6 rounded-full bg-slate-200 border-2 border-white dark:border-slate-800 flex items-center justify-center text-slate-500 text-[9px] font-bold">+{{ $folder->shares->count() - 5 }}</div>@endif
            </div>
            @endif
            <div class="flex items-center gap-2 pt-1 border-t border-slate-100 dark:border-white/5">
                <button wire:click="openShare({{ $folder->id }})" class="flex-1 flex items-center justify-center gap-1 text-xs font-bold text-primary hover:bg-primary/5 py-1.5 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-sm">share</span> Share
                </button>
                <button wire:click="togglePublic({{ $folder->id }})" class="flex-1 flex items-center justify-center gap-1 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5 py-1.5 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-sm">{{ $folder->is_public ? 'lock' : 'public' }}</span>
                    {{ $folder->is_public ? 'Make Private' : 'Make Public' }}
                </button>
                <button wire:click="confirmDelete({{ $folder->id }})" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:text-error transition-colors rounded-lg hover:bg-red-50">
                    <span class="material-symbols-outlined text-sm">delete</span>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</section>

<section>
    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-4">Shared With Me</h3>
    @if($sharedWithMe->isEmpty())
    <div class="glass-card rounded-2xl p-10 text-center text-slate-400">
        <span class="material-symbols-outlined text-4xl block mb-2">folder_shared</span>
        <p class="text-sm">No folders have been shared with you yet.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($sharedWithMe as $folder)
        @php $isUnlocked=in_array($folder->id,$unlockedFolders); $myShare=$folder->shares->firstWhere('user_id',auth()->id()); $permission=$folder->is_public?'view':($myShare?->permission??'view'); @endphp
        <div class="glass-card rounded-2xl p-5 flex flex-col gap-3">
            <button wire:click="openFolder({{ $folder->id }})" @if($folder->is_encrypted && !$isUnlocked) disabled @endif
                class="flex items-start gap-3 text-left w-full disabled:opacity-60 disabled:cursor-not-allowed">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 {{ $folder->is_encrypted ? 'bg-amber-100 text-amber-600' : 'bg-indigo-100 text-indigo-600' }}">
                    <span class="material-symbols-outlined">{{ $folder->is_encrypted && !$isUnlocked ? 'lock' : 'folder_shared' }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-800 dark:text-slate-100 truncate font-headline">{{ $folder->name }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">by {{ $folder->owner->name }}</p>
                </div>
            </button>
            <div class="flex flex-wrap gap-1.5">
                @if($folder->is_public)<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Public</span>@endif
                @if($folder->is_encrypted)<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $isUnlocked ? 'Unlocked' : 'Encrypted' }}</span>@endif
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 capitalize">{{ $permission }}</span>
            </div>
            @if($folder->is_encrypted && !$isUnlocked)
            <div class="flex gap-2">
                <input wire:model="unlockPassphrase.{{ $folder->id }}" type="password" placeholder="Passphrase to open" class="flex-1 px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                <button wire:click="tryUnlock({{ $folder->id }})" class="px-3 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-lg hover:opacity-90">Unlock</button>
            </div>
            @if(isset($unlockErrors[$folder->id]))<p class="text-xs text-error -mt-1">{{ $unlockErrors[$folder->id] }}</p>@endif
            @endif
        </div>
        @endforeach
    </div>
    @endif
</section>
@endif {{-- end view: folders --}}

{{-- ═══════════════════════════════════════════════════════════════════════════
     VIEW: BROWSER
════════════════════════════════════════════════════════════════════════════ --}}
@if($view === 'browser' && $browserFolder)
@php $canEditBrowser = $browserFolder->owner_id === auth()->id() || ($browserFolder->shares->firstWhere('user_id', auth()->id())?->permission === 'edit'); @endphp

@if($browserSubfolders->count() > 0)
<div class="mb-8">
    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-4">Folders</h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        @foreach($browserSubfolders as $sub)
        <div class="group relative flex flex-col items-center gap-2 p-4 rounded-2xl border border-slate-100 dark:border-white/5 bg-white dark:bg-slate-800/40 hover:bg-slate-50 dark:hover:bg-white/5 hover:border-primary/20 transition-all cursor-pointer" wire:click="openFolder({{ $sub->id }})">
            <span class="material-symbols-outlined text-4xl text-primary/60 group-hover:text-primary transition-colors">folder</span>
            <p class="text-xs font-bold text-slate-700 dark:text-slate-200 text-center truncate w-full font-headline">{{ $sub->name }}</p>
            @if($sub->owner_id === auth()->id())
            <button wire:click.stop="confirmDeleteSub({{ $sub->id }})" class="absolute top-1.5 right-1.5 w-5 h-5 flex items-center justify-center rounded-md text-slate-300 hover:text-error hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all">
                <span class="material-symbols-outlined text-xs">close</span>
            </button>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

@if($browserDocuments->count() > 0)
<div>
    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-4">Documents</h3>
    <div class="glass-card rounded-2xl overflow-hidden">
        @foreach($browserDocuments as $doc)
        @php
            $docIcon  = match($doc->type) { 'note'=>'sticky_note_2','docx'=>'article','spreadsheet'=>'table_chart',default=>'description' };
            $docColor = match($doc->type) { 'note'=>'bg-yellow-100 text-yellow-600','docx'=>'bg-blue-100 text-blue-700','spreadsheet'=>'bg-green-100 text-green-700',default=>'bg-slate-100 text-slate-600' };
            $docLabel = match($doc->type) { 'note'=>'Note','docx'=>'Word Doc','spreadsheet'=>'Spreadsheet',default=>'Document' };
        @endphp
        <div class="flex items-center gap-4 px-5 py-3.5 border-b border-slate-100 dark:border-white/5 last:border-0 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 {{ $docColor }}">
                <span class="material-symbols-outlined text-sm">{{ $docIcon }}</span>
            </div>
            <div class="flex-1 min-w-0 cursor-pointer" wire:click="openEditDoc({{ $doc->id }})">
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate font-headline">{{ $doc->title }}</p>
                <p class="text-xs text-slate-400 mt-0.5"><span class="font-medium text-slate-500">{{ $docLabel }}</span> &middot; {{ $doc->creator->name }} &middot; {{ $doc->updated_at->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button wire:click="openEditDoc({{ $doc->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-primary hover:bg-primary/5 transition-colors" title="Open">
                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                </button>
                @if($canEditBrowser)
                <button wire:click="confirmDeleteDoc({{ $doc->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-error hover:bg-red-50 transition-colors" title="Delete">
                    <span class="material-symbols-outlined text-sm">delete</span>
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if($browserSubfolders->count() === 0 && $browserDocuments->count() === 0)
<div class="flex flex-col items-center justify-center py-24 text-slate-400">
    <span class="material-symbols-outlined text-6xl mb-4 opacity-30">folder_open</span>
    <p class="text-base font-semibold">This folder is empty</p>
    @if($canEditBrowser)<p class="text-sm mt-1">Use the buttons above to add folders or documents.</p>@endif
</div>
@endif
@endif {{-- end view: browser --}}


{{-- ═══════════════════════════════════════════════════════════════════════════
     VIEW: EDITOR
════════════════════════════════════════════════════════════════════════════ --}}
@if($view === 'editor')
<div
    x-data="{
        docType: @entangle('docType').live,
        execCmd(cmd,val=null){ document.execCommand(cmd,false,val); this.syncDocx(); },
        syncDocx(){ const el=document.getElementById('docx-editor'); if(el) $wire.set('docContent',el.innerHTML); },
        initDocx(){
            const el=document.getElementById('docx-editor');
            if(el){ el.innerHTML=$wire.get('docContent')||''; el.addEventListener('input',()=>this.syncDocx()); }
        },
        cells: @entangle('ssCells').live,
        rows: @entangle('ssRows'),
        cols: @entangle('ssCols'),
        activeCell: null,
        colLabels: ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'],
        cellKey(r,c){ return 'R'+(r+1)+'C'+(c+1); },
        cellVal(r,c){ return this.cells[this.cellKey(r,c)]?.v??''; },
        setCellVal(r,c,v){ const k=this.cellKey(r,c); if(!this.cells[k]) this.cells[k]={}; this.cells[k].v=v; },
        isBold(r,c)     { return !!this.cells[this.cellKey(r,c)]?.bold; },
        isItalic(r,c)   { return !!this.cells[this.cellKey(r,c)]?.italic; },
        isUnder(r,c)    { return !!this.cells[this.cellKey(r,c)]?.underline; },
        cellAlign(r,c)  { return this.cells[this.cellKey(r,c)]?.align??'left'; },
        toggleBold(r,c)  { const k=this.cellKey(r,c); if(!this.cells[k]) this.cells[k]={}; this.cells[k].bold=!this.cells[k].bold; },
        toggleItalic(r,c){ const k=this.cellKey(r,c); if(!this.cells[k]) this.cells[k]={}; this.cells[k].italic=!this.cells[k].italic; },
        toggleUnder(r,c) { const k=this.cellKey(r,c); if(!this.cells[k]) this.cells[k]={}; this.cells[k].underline=!this.cells[k].underline; },
        setAlign(r,c,a)  { const k=this.cellKey(r,c); if(!this.cells[k]) this.cells[k]={}; this.cells[k].align=a; },
        focusNext(r,c,dir='right'){
            let nr=r,nc=c;
            if(dir==='right') nc=c+1; else if(dir==='down') nr=r+1; else if(dir==='left') nc=Math.max(0,c-1); else nr=Math.max(0,r-1);
            const n=document.getElementById('cell-'+nr+'-'+nc); if(n) n.focus();
        },
        saveSheet(){ $wire.syncCells(this.cells).then(()=>$wire.saveDoc()); },
        get ap(){ if(!this.activeCell) return null; const p=this.activeCell.replace('R','').split('C').map(n=>parseInt(n)-1); return {r:p[0],c:p[1]}; }
    }"
    x-init="if(docType==='docx') $nextTick(()=>initDocx())"
    class="flex flex-col rounded-2xl overflow-hidden shadow-sm border border-slate-200 dark:border-white/5"
    style="height:calc(100vh - 10rem);">

    {{-- ── Top bar: type picker · title · save ── --}}
    <div class="flex items-center gap-3 px-5 py-2.5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-white/5 flex-shrink-0">
        @if(!$editingDocId)
        <div class="flex bg-slate-100 dark:bg-slate-800 rounded-xl p-1 gap-0.5 flex-shrink-0">
            @foreach(['document'=>['description','Document','text-slate-700'],'note'=>['sticky_note_2','Note','text-amber-600'],'docx'=>['article','Word','text-blue-600'],'spreadsheet'=>['table_chart','Spreadsheet','text-green-600']] as $t=>[$icon,$label,$ac])
            <button wire:click="$set('docType','{{ $t }}')" x-on:click="docType='{{ $t }}'; if('{{ $t }}'==='docx') $nextTick(()=>initDocx())"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $docType===$t ? 'bg-white dark:bg-slate-700 shadow-sm '.$ac : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}">
                <span class="material-symbols-outlined text-[16px]">{{ $icon }}</span><span class="hidden sm:inline">{{ $label }}</span>
            </button>
            @endforeach
        </div>
        @else
        @php $bm=['note'=>['sticky_note_2','Note','bg-amber-100 text-amber-700'],'docx'=>['article','Word Doc','bg-blue-100 text-blue-700'],'spreadsheet'=>['table_chart','Spreadsheet','bg-green-100 text-green-700'],'document'=>['description','Document','bg-slate-100 text-slate-600']]; [$bi,$bl,$bc]=$bm[$docType]??$bm['document']; @endphp
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl {{ $bc }} flex-shrink-0 text-xs font-bold">
            <span class="material-symbols-outlined text-[16px]">{{ $bi }}</span>{{ $bl }}
        </div>
        @endif
        <input wire:model="docTitle" type="text" placeholder="Untitled…"
            class="flex-1 px-2 py-1.5 bg-transparent border-none text-lg font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-0 font-headline placeholder:text-slate-300 min-w-0"/>
        @error('docTitle')<p class="text-xs text-error flex-shrink-0 whitespace-nowrap">{{ $message }}</p>@enderror
        <button x-on:click="docType==='spreadsheet'?saveSheet():(docType==='docx'?(syncDocx(),$wire.saveDoc()):$wire.saveDoc())"
            class="flex items-center gap-2 px-5 py-2 bg-primary text-on-primary text-sm font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all flex-shrink-0 shadow-sm shadow-primary/20">
            <span class="material-symbols-outlined text-[18px]">save</span><span class="hidden sm:inline">Save</span>
        </button>
        <button wire:click="closeEditor" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors flex-shrink-0">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>

    {{-- ── Plain document / note: paper-like writing surface ── --}}
    <div x-show="docType==='document'||docType==='note'" class="flex-1 overflow-y-auto bg-slate-100 dark:bg-slate-950 p-6 flex justify-center">
        <textarea wire:model="docContent" placeholder="Start writing…"
            class="w-full max-w-3xl px-12 py-10 bg-white dark:bg-slate-900 shadow-sm rounded-xl border-0 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-0 resize-none leading-8 text-[15px]"
            style="min-height:calc(100vh - 16rem);"
            :class="docType==='note'?'font-mono text-sm leading-6':'font-serif'"></textarea>
    </div>

    {{-- ── Word doc: ribbon toolbar + paper canvas ── --}}
    <div x-show="docType==='docx'" class="flex flex-col flex-1 overflow-hidden bg-slate-100 dark:bg-slate-950">
        <style>
            #docx-toolbar .material-symbols-outlined {
                font-family: 'Material Symbols Outlined' !important;
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24 !important;
                font-style: normal !important;
                font-weight: normal !important;
                font-size: 18px !important;
                line-height: 1 !important;
                letter-spacing: normal !important;
                text-transform: none !important;
                display: inline-block;
            }
        </style>
        {{-- Ribbon --}}
        <div id="docx-toolbar" class="flex flex-wrap items-center gap-0.5 px-3 py-1.5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-white/5 flex-shrink-0 shadow-sm" style="font-family:'Inter',sans-serif;font-variation-settings:normal;">
            <select onchange="document.execCommand('fontName',false,this.value)" class="text-xs border border-slate-200 dark:border-white/10 rounded-lg px-2 py-1.5 bg-white dark:bg-slate-800 dark:text-slate-100 focus:outline-none">
                <option>Times New Roman</option><option>Arial</option><option>Georgia</option><option>Courier New</option><option>Verdana</option>
            </select>
            <select onchange="document.execCommand('fontSize',false,this.value)" class="text-xs border border-slate-200 dark:border-white/10 rounded-lg px-2 py-1.5 bg-white dark:bg-slate-800 dark:text-slate-100 focus:outline-none ml-1 mr-2 w-14">
                <option value="1">8</option><option value="2">10</option><option value="3" selected>12</option><option value="4">14</option><option value="5">18</option><option value="6">24</option><option value="7">36</option>
            </select>
            <div class="w-px h-6 bg-slate-200 dark:bg-white/10 mx-1"></div>
            @foreach(['h1'=>'H1','h2'=>'H2','h3'=>'H3','p'=>'¶'] as $tag=>$lbl)
            <button type="button" @click="execCmd('formatBlock','{{ $tag }}')" class="px-2.5 h-8 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 text-xs font-bold transition-colors">{{ $lbl }}</button>
            @endforeach
            <div class="w-px h-6 bg-slate-200 dark:bg-white/10 mx-1"></div>
            @foreach([['bold','format_bold'],['italic','format_italic'],['underline','format_underline'],['strikeThrough','strikethrough_s']] as [$cmd,$icon])
            <button type="button" @click="execCmd('{{ $cmd }}')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                <span class="material-symbols-outlined text-[18px]" style="font-family:'Material Symbols Outlined';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-style:normal;">{{ $icon }}</span>
            </button>
            @endforeach
            <div class="w-px h-6 bg-slate-200 dark:bg-white/10 mx-1"></div>
            @foreach([['justifyLeft','format_align_left'],['justifyCenter','format_align_center'],['justifyRight','format_align_right'],['justifyFull','format_align_justify']] as [$cmd,$icon])
            <button type="button" @click="execCmd('{{ $cmd }}')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                <span class="material-symbols-outlined text-[18px]" style="font-family:'Material Symbols Outlined';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-style:normal;">{{ $icon }}</span>
            </button>
            @endforeach
            <div class="w-px h-6 bg-slate-200 dark:bg-white/10 mx-1"></div>
            @foreach([['insertUnorderedList','format_list_bulleted'],['insertOrderedList','format_list_numbered'],['indent','format_indent_increase'],['outdent','format_indent_decrease']] as [$cmd,$icon])
            <button type="button" @click="execCmd('{{ $cmd }}')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                <span class="material-symbols-outlined text-[18px]" style="font-family:'Material Symbols Outlined';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-style:normal;">{{ $icon }}</span>
            </button>
            @endforeach
            <div class="w-px h-6 bg-slate-200 dark:bg-white/10 mx-1"></div>
            <label class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 cursor-pointer" title="Text colour">
                <span class="material-symbols-outlined text-[18px]" style="font-family:'Material Symbols Outlined';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-style:normal;">format_color_text</span>
                <input type="color" class="sr-only" onchange="document.execCommand('foreColor',false,this.value)"/>
            </label>
            <label class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 cursor-pointer" title="Highlight">
                <span class="material-symbols-outlined text-[18px]" style="font-family:'Material Symbols Outlined';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-style:normal;">highlight</span>
                <input type="color" class="sr-only" onchange="document.execCommand('hiliteColor',false,this.value)"/>
            </label>
            <button type="button" @click="const u=prompt('URL:');if(u)execCmd('createLink',u)" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors" title="Link">
                <span class="material-symbols-outlined text-[18px]" style="font-family:'Material Symbols Outlined';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-style:normal;">link</span>
            </button>
            <button type="button" @click="execCmd('removeFormat')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors" title="Clear formatting">
                <span class="material-symbols-outlined text-[18px]" style="font-family:'Material Symbols Outlined';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-style:normal;">format_clear</span>
            </button>
        </div>
        {{-- Paper canvas --}}
        <div class="flex-1 overflow-y-auto p-8 flex justify-center">
            <div class="w-full max-w-3xl bg-white dark:bg-slate-900 shadow-md rounded-lg" style="min-height:calc(100vh - 18rem);padding:3.5rem 4.5rem;">
                <div id="docx-editor" contenteditable="true" spellcheck="true"
                    class="outline-none text-slate-800 dark:text-slate-100 min-h-full
                           [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:mb-4 [&_h1]:mt-2
                           [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:mb-3 [&_h2]:mt-2
                           [&_h3]:text-xl  [&_h3]:font-semibold [&_h3]:mb-2 [&_h3]:mt-1
                           [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:mb-2
                           [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:mb-2
                           [&_a]:text-primary [&_a]:underline"
                    style="font-family:'Times New Roman',serif;font-size:13pt;line-height:1.9;">
                </div>
            </div>
        </div>
    </div>

    {{-- ── Spreadsheet: formula bar + full-width grid + status bar ── --}}
    <div x-show="docType==='spreadsheet'" class="flex flex-col flex-1 overflow-hidden bg-white dark:bg-slate-900">

        {{-- Spreadsheet toolbar --}}
        <div class="flex items-center gap-1 px-3 py-1.5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-white/5 flex-shrink-0">
            {{-- Format buttons --}}
            <button type="button" x-on:click="if(ap) toggleBold(ap.r,ap.c)"
                :class="ap&&isBold(ap.r,ap.c)?'bg-primary/10 text-primary':'text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10'"
                class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors font-bold text-sm">B</button>
            <button type="button" x-on:click="if(ap) toggleItalic(ap.r,ap.c)"
                :class="ap&&isItalic(ap.r,ap.c)?'bg-primary/10 text-primary':'text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10'"
                class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors italic text-sm">I</button>
            <button type="button" x-on:click="if(ap) toggleUnder(ap.r,ap.c)"
                :class="ap&&isUnder(ap.r,ap.c)?'bg-primary/10 text-primary':'text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10'"
                class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors underline text-sm">U</button>
            <div class="w-px h-5 bg-slate-200 dark:bg-white/10 mx-1"></div>
            @foreach([['left','format_align_left'],['center','format_align_center'],['right','format_align_right']] as [$a,$icon])
            <button type="button" x-on:click="if(ap) setAlign(ap.r,ap.c,'{{ $a }}')"
                :class="ap&&cellAlign(ap.r,ap.c)==='{{ $a }}'?'bg-primary/10 text-primary':'text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10'"
                class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
            </button>
            @endforeach
            <div class="w-px h-5 bg-slate-200 dark:bg-white/10 mx-1"></div>
            {{-- Formula bar --}}
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <div class="w-16 px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg text-xs font-mono text-slate-500 text-center flex-shrink-0 select-none" x-text="activeCell??'—'"></div>
                <div class="flex items-center flex-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg px-3 py-1 gap-2">
                    <span class="text-slate-300 text-sm font-mono flex-shrink-0 select-none">fx</span>
                    <input type="text" :value="ap?cellVal(ap.r,ap.c):''" @input="if(ap) setCellVal(ap.r,ap.c,$event.target.value)"
                        placeholder="Select a cell to edit" class="flex-1 bg-transparent text-xs text-slate-700 dark:text-slate-200 focus:outline-none font-mono"/>
                </div>
            </div>
            <div class="w-px h-5 bg-slate-200 dark:bg-white/10 mx-1"></div>
            <button type="button" @click="rows++" class="flex items-center gap-0.5 px-2 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[14px]">add</span>Row</button>
            <button type="button" @click="if(cols<26)cols++" class="flex items-center gap-0.5 px-2 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[14px]">add</span>Col</button>
            <button type="button" @click="if(rows>1)rows--" class="flex items-center gap-0.5 px-2 py-1.5 text-xs font-bold text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[14px]">remove</span>Row</button>
            <button type="button" @click="if(cols>1)cols--" class="flex items-center gap-0.5 px-2 py-1.5 text-xs font-bold text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[14px]">remove</span>Col</button>
        </div>

        {{-- Grid --}}
        <div class="flex-1 overflow-auto">
            <table class="border-collapse" style="min-width:max-content;table-layout:fixed;">
                <colgroup>
                    <col style="width:3rem;">
                    <template x-for="c in cols" :key="'cg'+c"><col style="width:120px;"></template>
                </colgroup>
                <thead class="sticky top-0 z-20">
                    <tr>
                        <th class="h-8 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 sticky left-0 z-30"></th>
                        <template x-for="c in cols" :key="'ch'+c">
                            <th class="h-8 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-slate-500 font-bold text-center text-xs px-2 select-none" x-text="colLabels[c-1]??c"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in rows" :key="'row'+r">
                        <tr>
                            <td class="h-8 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-slate-400 text-center text-xs font-medium sticky left-0 z-10 select-none" x-text="r"></td>
                            <template x-for="c in cols" :key="'c'+r+'-'+c">
                                <td class="h-8 border border-slate-200 dark:border-white/10 p-0 bg-white dark:bg-slate-900 hover:bg-blue-50/30 dark:hover:bg-white/5"
                                    :class="activeCell===cellKey(r-1,c-1)?'outline outline-2 outline-primary outline-offset-[-1px] z-10 bg-white dark:bg-slate-900':''">
                                    <input type="text"
                                        :id="'cell-'+(r-1)+'-'+(c-1)"
                                        :value="cellVal(r-1,c-1)"
                                        @input="setCellVal(r-1,c-1,$event.target.value)"
                                        @focus="activeCell=cellKey(r-1,c-1)"
                                        @keydown.tab.prevent="focusNext(r-1,c-1,'right')"
                                        @keydown.enter.prevent="focusNext(r-1,c-1,'down')"
                                        @keydown.shift.tab.prevent="focusNext(r-1,c-1,'left')"
                                        :class="{'font-bold':isBold(r-1,c-1),'italic':isItalic(r-1,c-1),'underline':isUnder(r-1,c-1),'text-left':cellAlign(r-1,c-1)==='left','text-center':cellAlign(r-1,c-1)==='center','text-right':cellAlign(r-1,c-1)==='right'}"
                                        class="w-full h-full px-2 bg-transparent focus:outline-none text-slate-800 dark:text-slate-100 text-xs"/>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Status bar --}}
        <div class="flex items-center gap-4 px-4 py-1.5 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-white/5 flex-shrink-0 select-none">
            <span class="text-[11px] text-slate-400" x-text="activeCell?'Cell: '+activeCell:'No cell selected'"></span>
            <span class="text-[11px] text-slate-300 ml-auto" x-text="rows+' rows × '+cols+' columns'"></span>
        </div>
    </div>

</div>
@endif {{-- end view: editor --}}


{{-- ═══════════════════════════════════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════════════════════════════════ --}}
@if($showCreateModal)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" wire:click.self="$set('showCreateModal', false)">
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-md p-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-extrabold font-headline text-slate-900 dark:text-slate-100">{{ $createParentId ? 'New Subfolder' : 'New Folder' }}</h3>
            <button wire:click="$set('showCreateModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Name *</label>
                <input wire:model="name" type="text" placeholder="e.g. Q1 Reports" autofocus class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none dark:text-slate-100"/>
                @error('name')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Description</label>
                <textarea wire:model="description" rows="2" placeholder="Optional..." class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none resize-none dark:text-slate-100"></textarea>
            </div>
            @if(!$createParentId)
            <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 dark:border-white/10 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                <input wire:model.live="isEncrypted" type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary"/>
                <div><p class="text-sm font-bold text-slate-800 dark:text-slate-100">Encrypt this folder</p><p class="text-xs text-slate-400">Requires a passphrase to open</p></div>
                <span class="material-symbols-outlined ml-auto text-amber-500">lock</span>
            </label>
            @if($isEncrypted)
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Passphrase *</label>
                <input wire:model="encryptionPassphrase" type="password" placeholder="Min. 4 characters" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-amber-300 text-sm focus:ring-2 focus:ring-amber-400/30 focus:outline-none dark:text-slate-100"/>
                @error('encryptionPassphrase')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
            </div>
            @endif
            <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 dark:border-white/10 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                <input wire:model="isPublic" type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary"/>
                <div><p class="text-sm font-bold text-slate-800 dark:text-slate-100">Share with everyone</p><p class="text-xs text-slate-400">All users in the organisation can see this folder</p></div>
                <span class="material-symbols-outlined ml-auto text-green-500">public</span>
            </label>
            @endif
        </div>
        <div class="flex gap-3 mt-8">
            <button wire:click="$set('showCreateModal', false)" class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-white/10 text-sm font-bold text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">Cancel</button>
            <button wire:click="createFolder" class="flex-1 py-3 rounded-xl bg-primary text-on-primary text-sm font-bold hover:opacity-90 transition-opacity shadow-sm shadow-primary/20">Create</button>
        </div>
    </div>
</div>
@endif

@if($showShareModal)
@php $sharingFolder = $myFolders->firstWhere('id', $sharingFolderId); @endphp
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" wire:click.self="$set('showShareModal', false)">
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-lg p-8 flex flex-col" style="max-height:90vh;">
        <div class="flex items-center justify-between mb-6 flex-shrink-0">
            <div>
                <h3 class="text-xl font-extrabold font-headline text-slate-900 dark:text-slate-100">Share Folder</h3>
                @if($sharingFolder)<p class="text-sm text-slate-400 mt-0.5">"{{ $sharingFolder->name }}"</p>@endif
            </div>
            <button wire:click="$set('showShareModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="relative mb-4 flex-shrink-0">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
            <input wire:model.live.debounce.250ms="shareSearch" type="text" placeholder="Search people..." class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none dark:text-slate-100"/>
        </div>
        @if($searchResults->count() > 0)
        <div class="mb-4 flex-shrink-0 space-y-1 max-h-48 overflow-y-auto">
            @foreach($searchResults as $person)
            @php $isSel=isset($selectedUsers[$person->id]); @endphp
            <button wire:click="toggleUserShare({{ $person->id }})" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl transition-colors text-left {{ $isSel?'bg-primary/10 border border-primary/20':'hover:bg-slate-50 dark:hover:bg-white/5' }}">
                <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm flex-shrink-0">{{ strtoupper(substr($person->name,0,2)) }}</div>
                <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $person->name }}</p><p class="text-xs text-slate-400 truncate">{{ $person->position??'' }}</p></div>
                @if($isSel)<span class="material-symbols-outlined text-primary text-sm">check_circle</span>@endif
            </button>
            @endforeach
        </div>
        @endif
        @if(count($selectedUsers) > 0)
        <div class="flex-1 overflow-y-auto mb-4">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">People with access</p>
            <div class="space-y-2">
                @foreach($selectedUsers as $userId => $permission)
                @php $u=\App\Models\User::find($userId); @endphp
                @if($u)
                <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">{{ strtoupper(substr($u->name,0,2)) }}</div>
                    <p class="flex-1 text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $u->name }}</p>
                    <select wire:change="setPermission({{ $userId }}, $event.target.value)" class="text-xs border border-slate-200 dark:border-white/10 rounded-lg px-2 py-1 bg-white dark:bg-slate-700 dark:text-slate-100 focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="view" {{ $permission==='view'?'selected':'' }}>View</option>
                        <option value="edit" {{ $permission==='edit'?'selected':'' }}>Edit</option>
                    </select>
                    <button wire:click="toggleUserShare({{ $userId }})" class="text-slate-300 hover:text-error transition-colors"><span class="material-symbols-outlined text-sm">close</span></button>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @else
        <div class="flex-1 flex items-center justify-center text-slate-400 text-sm py-6">Search for people above to share with</div>
        @endif
        <div class="flex gap-3 flex-shrink-0 pt-4 border-t border-slate-100 dark:border-white/5">
            <button wire:click="$set('showShareModal', false)" class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-white/10 text-sm font-bold text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">Cancel</button>
            <button wire:click="saveShares" class="flex-1 py-3 rounded-xl bg-primary text-on-primary text-sm font-bold hover:opacity-90 transition-opacity shadow-sm shadow-primary/20">Save & Notify</button>
        </div>
    </div>
</div>
@endif

@foreach([
    ['show'=>$showDeleteConfirm,    'action'=>'deleteFolder','close'=>'showDeleteConfirm',    'msg'=>'Delete this folder and all its contents?'],
    ['show'=>$showDeleteDocConfirm, 'action'=>'deleteDoc',   'close'=>'showDeleteDocConfirm', 'msg'=>'Delete this document permanently?'],
    ['show'=>$showDeleteSubConfirm, 'action'=>'deleteSub',   'close'=>'showDeleteSubConfirm', 'msg'=>'Delete this subfolder and all its contents?'],
] as $dlg)
@if($dlg['show'])
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-sm p-8 text-center">
        <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-error text-2xl">delete_forever</span>
        </div>
        <h3 class="text-lg font-extrabold font-headline text-slate-900 dark:text-slate-100 mb-2">Are you sure?</h3>
        <p class="text-sm text-slate-500 mb-8">{{ $dlg['msg'] }} This cannot be undone.</p>
        <div class="flex gap-3">
            <button wire:click="$set('{{ $dlg['close'] }}', false)" class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-white/10 text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors">Cancel</button>
            <button wire:click="{{ $dlg['action'] }}" class="flex-1 py-3 rounded-xl bg-error text-on-error text-sm font-bold hover:opacity-90 transition-opacity">Delete</button>
        </div>
    </div>
</div>
@endif
@endforeach

</div>{{-- end root div --}}
