<div>
    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <h2 class="text-2xl font-bold text-gray-800 mb-6">Leave Approval Queue</h2>

    <!-- Status Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex space-x-4">
            <button wire:click="$set('statusFilter', 'pending')" 
                class="px-4 py-2 rounded-md {{ $statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                Pending
            </button>
            <button wire:click="$set('statusFilter', 'approved')" 
                class="px-4 py-2 rounded-md {{ $statusFilter === 'approved' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                Approved
            </button>
            <button wire:click="$set('statusFilter', 'rejected')" 
                class="px-4 py-2 rounded-md {{ $statusFilter === 'rejected' ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                Rejected
            </button>
            <button wire:click="$set('statusFilter', '')" 
                class="px-4 py-2 rounded-md {{ $statusFilter === '' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                All
            </button>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leave Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leaveRequests as $request)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $request->user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $request->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $request->leaveType->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $request->start_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $request->end_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $request->total_days }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                @if($request->status === 'approved') bg-green-100 text-green-800
                                @elseif($request->status === 'rejected') bg-red-100 text-red-800
                                @elseif($request->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($request->status === 'pending')
                            <button wire:click="approveLeave({{ $request->id }})" 
                                wire:confirm="Approve this leave request?"
                                class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                            <button wire:click="rejectLeave({{ $request->id }})" 
                                wire:confirm="Reject this leave request?"
                                class="text-red-600 hover:text-red-900">Reject</button>
                            @else
                            <span class="text-gray-400 text-sm">
                                {{ $request->approvedBy?->name ?? '-' }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No leave requests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $leaveRequests->links() }}
        </div>
    </div>
</div>
