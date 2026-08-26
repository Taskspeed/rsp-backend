<?php

namespace Database\Seeders;

use App\Models\EmployeeAssign;
use Illuminate\Database\Seeder;

class EmployeeAssignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
     EmployeeAssign::create([
            'control_no' => '022395',
           'office' => 'OFFICE OF THE CITY INFORMATION AND COMMUNICATIONS TECHNOLOGY MANAGEMENT OFFICER',
           'office2' => NULL,
           'group' => NULL,
            'division' => 'TECHNICAL DIVISION',
            'section' => 'SYSTEMS PROCESS MANAGEMENT SECTION',
            'unit' => NULL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
