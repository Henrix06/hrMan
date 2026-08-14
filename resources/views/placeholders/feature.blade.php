<x-app-layout>
    <div class="mx-auto max-w-4xl">
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-8 shadow-sm">
            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-800">
                Livewire Removed
            </span>
            <h1 class="mt-4 text-3xl font-black text-slate-900">{{ $title }}</h1>
            <p class="mt-3 text-base leading-7 text-slate-700">{{ $description }}</p>
            <p class="mt-4 text-sm text-slate-500">
                The route remains registered so navigation does not break, but the interactive Livewire implementation has been removed.
            </p>
        </div>
    </div>
</x-app-layout>
