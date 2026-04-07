<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'description' => 'Paid time off for vacation and personal matters',
                'default_days' => 21,
                'paid' => true,
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Leave for illness or medical appointments',
                'default_days' => 10,
                'paid' => true,
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'description' => 'Leave for childbirth and childcare',
                'default_days' => 90,
                'paid' => true,
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Paternity Leave',
                'description' => 'Leave for fathers after childbirth',
                'default_days' => 10,
                'paid' => true,
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Bereavement Leave',
                'description' => 'Leave following the death of a family member',
                'default_days' => 5,
                'paid' => true,
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Unpaid Leave',
                'description' => 'Leave without pay for extended time off',
                'default_days' => 0,
                'paid' => false,
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Public Holiday',
                'description' => 'Official government declared holidays',
                'default_days' => 0,
                'paid' => true,
                'requires_approval' => false,
                'active' => true,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                ['name' => $leaveType['name']],
                $leaveType
            );
        }
    }
}
