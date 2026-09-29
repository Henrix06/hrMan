<x-app-layout>
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-6">
        <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">lock</span>
        <h1 class="font-headline font-bold text-2xl text-slate-800 dark:text-slate-100 mb-2">
            Module Not Available
        </h1>
        <p class="text-slate-500 dark:text-slate-400 max-w-md mb-8">
            The <strong>{{ ucwords(str_replace('_', ' ', $moduleKey ?? 'requested')) }}</strong> module is not included in your current plan.
            Upgrade to unlock this feature.
        </p>
        @role('Super Admin')
        <a href="{{ route('plan.index') }}"
           class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-2xl font-semibold text-sm hover:bg-primary-dim transition-colors">
            <span class="material-symbols-outlined text-[18px]">upgrade</span>
            Upgrade your plan
        </a>
        @else
        <p class="text-xs text-slate-400 max-w-md mb-3">Ask a workspace administrator to upgrade your plan.</p>
        <a href="{{ route('pricing') }}"
           class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-2xl font-semibold text-sm hover:bg-primary-dim transition-colors">
            <span class="material-symbols-outlined text-[18px]">upgrade</span>
            View Pricing Plans
        </a>
        @endrole
        <a href="{{ route('dashboard') }}" class="mt-4 text-sm text-slate-400 hover:text-slate-600 transition-colors">
            Back to Dashboard
        </a>
    </div>
</x-app-layout>
