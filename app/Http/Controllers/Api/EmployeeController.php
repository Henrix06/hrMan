<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeDetailResource;
use App\Http\Resources\EmployeeResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $employees = User::query()
            ->with(['department:id,name,code', 'roles:id,name'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('search'), fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('employee_id', 'ilike', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->paginate(50);

        return EmployeeResource::collection($employees);
    }

    public function show(User $employee): EmployeeDetailResource
    {
        $employee->load([
            'department:id,name,code',
            'manager:id,name',
            'roles:id,name',
            'leaveBalances.leaveType:id,name',
        ]);

        return new EmployeeDetailResource($employee);
    }
}
