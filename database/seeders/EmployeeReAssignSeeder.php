<?php

namespace Database\Seeders;

use App\Models\EmployeeReAssign;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeReAssignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        
        // insert roles
        EmployeeReAssign::create([
            'control_no' => '022485',
           'office' => 'OFFICE OF THE CITY HUMAN RESOURCE MANAGEMENT OFFICER',
           'office2' => NULL,
           'group' => NULL,
            'division' => 'ADMINISTRATIVE & SUPPORT DIVISION',
            'section' => 'EMPLOYEES PERFORMANCE MANAGEMENT WELFARE AND INCENTIVES SECTION',
            'unit' => NULL,
            're_assign_date' => NULL,
           'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}
