<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trial
    |--------------------------------------------------------------------------
    | Length of the complimentary trial granted to a new company and to any
    | company that switches onto a paid plan without an active subscription.
    */
    'trial_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Available Modules
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'employee_management' => 'Employee Management',
        'accounting' => 'Accounting & Claims',
        'leave_management' => 'Leave Management',
        'payroll' => 'Payroll Processing',
        'asset_management' => 'Asset Management',
        'task_management' => 'Task Management',
        'sales' => 'Sales & Transport',
        'announcements' => 'Announcements',
        'chat' => 'Team Chat',
        'audit_logs' => 'Audit Logs',
        'departments' => 'Department Management',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    | monthly_price : price in USD per month
    | yearly_price  : price in USD per year (billed annually)
    | employee_limit: max employees (null = unlimited)
    | modules       : list of included module keys
    */
    'plans' => [

        'free' => [
            'name' => 'Free',
            'description' => 'For solo admins and micro-teams up to 5 people.',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'employee_limit' => 5,
            'highlighted' => false,
            'badge' => null,
            'modules' => [
                'employee_management',
                'accounting',
                'departments',
            ],
        ],

        'starter' => [
            'name' => 'Starter',
            'description' => 'For growing teams that need HR essentials.',
            'monthly_price' => 15,
            'yearly_price' => 144,   // 20% discount (15 × 12 × 0.8)
            'employee_limit' => 25,
            'highlighted' => false,
            'badge' => null,
            'modules' => [
                'employee_management',
                'accounting',
                'departments',
                'leave_management',
                'payroll',
                'announcements',
            ],
        ],

        'professional' => [
            'name' => 'Professional',
            'description' => 'Full HR suite for established businesses.',
            'monthly_price' => 20,
            'yearly_price' => 192,   // 20% discount (20 × 12 × 0.8)
            'employee_limit' => 100,
            'highlighted' => true,
            'badge' => 'Most Popular',
            'modules' => [
                'employee_management',
                'accounting',
                'departments',
                'leave_management',
                'payroll',
                'announcements',
                'asset_management',
                'task_management',
                'sales',
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'description' => 'Unlimited scale with full visibility and control. Contact us for pricing.',
            'monthly_price' => null,
            'yearly_price' => null,
            'employee_limit' => null,
            'highlighted' => false,
            'badge' => null,
            'modules' => [
                'employee_management',
                'accounting',
                'departments',
                'leave_management',
                'payroll',
                'announcements',
                'asset_management',
                'task_management',
                'sales',
                'chat',
                'audit_logs',
            ],
        ],

    ],

];
