<?php

namespace Database\Seeders;

use App\Models\Department;

class DepartmentSeeder extends \Illuminate\Database\Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Administration', 'code' => 'ADM', 'description' => 'Company administration and operations'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'People operations and talent management'],
            ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Software development and infrastructure'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Financial planning and accounting'],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Brand, growth and communications'],
            ['name' => 'Sales', 'code' => 'SLS', 'description' => 'Revenue and customer acquisition'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
