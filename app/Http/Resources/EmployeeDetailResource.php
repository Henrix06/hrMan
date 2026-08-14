<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'employee_id'  => $this->employee_id,
            'position'     => $this->position,
            'phone'        => $this->phone,
            'status'       => $this->status,
            'hire_date'    => $this->hire_date?->toDateString(),
            'department'   => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ]),
            'manager'      => $this->whenLoaded('manager', fn() => [
                'id'   => $this->manager->id,
                'name' => $this->manager->name,
            ]),
            'roles'        => $this->whenLoaded('roles', fn() =>
                $this->roles->pluck('name')
            ),
            'leave_balances' => $this->whenLoaded('leaveBalances', fn() =>
                $this->leaveBalances->map(fn($b) => [
                    'type'      => $b->leaveType?->name,
                    'balance'   => $b->balance,
                    'used'      => $b->used,
                    'year'      => $b->year,
                ])
            ),
        ];
    }
}
