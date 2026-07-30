<?php

namespace App\Services;

use App\Http\Resources\EmployeeActiveResource;
use App\Http\Resources\EmployeeResource;
use App\Models\EmployeeAssign;
use App\Models\EmployeeReAssign;
use App\Models\Office;
use App\Models\OfficeStructureOutside;
use App\Models\vwActive;
use App\Models\vwplantillastructure;

class OfficeService
{


    public function employee(string $office)
    {
        // employees whose home office matches
        $homeEmployees = vwActive::select('ControlNo', 'Office', 'Designation', 'Status', 'Name4')
            ->where('Office', $office)
            ->get();

        // control numbers of CONTRACTUAL/JOB ORDER/CASUAL employees explicitly assigned to this office
        $assignedControlNos = EmployeeAssign::where('office', $office)
            ->pluck('control_no')
            ->toArray();

        $nonPlantillaStatuses = ['CONTRACTUAL', 'JOB ORDER', 'CASUAL', 'HONORARIUM'];

        // filter: keep regular employees as-is; keep non-plantilla employees only if in EmployeeAssign
        $filterAssigned = function ($employee) use ($nonPlantillaStatuses, $assignedControlNos) {
            if (in_array(strtoupper($employee->Status), $nonPlantillaStatuses)) {
                return in_array($employee->ControlNo, $assignedControlNos);
            }
            return true;
        };

        $homeEmployees = $homeEmployees->filter($filterAssigned)->values();

        // control numbers of employees actively re-assigned INTO this office
        $reassignedInControlNos = EmployeeReAssign::where('office', $office)
            ->where('active', 1)
            ->pluck('control_no')
            ->toArray();

        // pull those employees' details too (their home Office may differ)
        $reassignedInEmployees = vwActive::select('ControlNo', 'Office', 'Designation', 'Status', 'Name4',)
            ->whereIn('ControlNo', $reassignedInControlNos)
            ->get()
            ->filter($filterAssigned)
            ->values();

        // merge + dedupe by ControlNo
        $employees = $homeEmployees->concat($reassignedInEmployees)
            ->unique('ControlNo')
            ->values();

        // re-check active reassignment status for the final combined list
        $reassignedControlNos = EmployeeReAssign::whereIn('control_no', $employees->pluck('ControlNo'))
            ->where('active', 1)
            ->pluck('control_no')
            ->toArray();

        $resource = $employees->map(
            fn($employee) => new EmployeeResource($employee, $reassignedControlNos)
        );

        return $resource;
    }

    // employeeListActive
    public function employeeListActive(string $office)
    {
        // employees whose home office matches
        $homeEmployees = vwActive::select('ControlNo', 'Office', 'Designation', 'Status', 'Name4')
            ->where('Office', $office)
            ->get();

        // control numbers of CONTRACTUAL/JOB ORDER/CASUAL employees explicitly assigned to this office
        $assignedControlNos = EmployeeAssign::where('office', $office)
            ->pluck('control_no')
            ->toArray();

        $nonPlantillaStatuses = ['CONTRACTUAL', 'JOB ORDER', 'CASUAL', 'HONORARIUM'];

        $filterAssigned = function ($employee) use ($nonPlantillaStatuses, $assignedControlNos) {
            if (in_array(strtoupper($employee->Status), $nonPlantillaStatuses)) {
                return in_array($employee->ControlNo, $assignedControlNos);
            }
            return true;
        };

        $homeEmployees = $homeEmployees->filter($filterAssigned)->values();

        // pull org-hierarchy fields for home employees from vwplantillastructure (default/home values)
        $homeControlNos = $homeEmployees->pluck('ControlNo')->toArray();

        $structureData = vwplantillastructure::select('ControlNo', 'office2', 'group', 'division', 'section', 'unit', 'Name4')
            ->whereIn('ControlNo', $homeControlNos)
            ->get()
            ->keyBy('ControlNo');

        $homeEmployees = $homeEmployees->map(function ($employee) use ($structureData) {
            $struct = $structureData->get($employee->ControlNo);

            $employee->office2  = $struct->office2  ?? null;
            $employee->group    = $struct->group    ?? null;
            $employee->division = $struct->division ?? null;
            $employee->section  = $struct->section  ?? null;
            $employee->unit     = $struct->unit     ?? null;

            return $employee;
        });

        // control numbers of employees actively re-assigned INTO this office
        $reassignedInControlNos = EmployeeReAssign::where('office', $office)
            ->where('active', 1)
            ->pluck('control_no')
            ->toArray();

        $reassignedInEmployees = vwplantillastructure::select('ControlNo', 'Office', 'position', 'Status', 'Name4', 'office', 'office2', 'group', 'division', 'section', 'unit')
            ->whereIn('ControlNo', $reassignedInControlNos)
            ->get()
            ->filter($filterAssigned)
            ->values();

        // merge + dedupe by ControlNo
        $employees = $homeEmployees->concat($reassignedInEmployees)
            ->unique('ControlNo')
            ->values();

        // re-check active reassignment status for the final combined list
        $reassignedControlNos = EmployeeReAssign::whereIn('control_no', $employees->pluck('ControlNo'))
            ->where('active', 1)
            ->pluck('control_no')
            ->toArray();

        // fetch EmployeeAssign records (non-plantilla, not re-assigned) keyed by control_no
        $assignData = EmployeeAssign::whereIn('control_no', $employees->pluck('ControlNo'))
            ->select('control_no', 'office', 'office2', 'group', 'division', 'section', 'unit')
            ->get()
            ->keyBy('control_no');

        // fetch EmployeeReAssign records (active, re-assigned) keyed by control_no
        $reassignData = EmployeeReAssign::whereIn('control_no', $employees->pluck('ControlNo'))
            ->where('active', 1)
            ->select('control_no', 'office', 'office2', 'group', 'division', 'section', 'unit')
            ->get()
            ->keyBy('control_no');

        // apply the office/org-hierarchy override rules
        $employees = $employees->map(function ($employee) use (
            $reassignedControlNos,
            $reassignData,
            $assignData,
            $nonPlantillaStatuses
        ) {
            $controlNo = $employee->ControlNo;
            $isReassigned = in_array($controlNo, $reassignedControlNos);

            if ($isReassigned) {
                // re-assign true -> use EmployeeReAssign values
                $reassign = $reassignData->get($controlNo);
                $employee->Office   = $reassign->office   ?? $employee->Office;
                $employee->office2  = $reassign->office2  ?? null;
                $employee->group    = $reassign->group    ?? null;
                $employee->division = $reassign->division ?? null;
                $employee->section  = $reassign->section  ?? null;
                $employee->unit     = $reassign->unit     ?? null;
            } elseif (in_array(strtoupper($employee->Status), $nonPlantillaStatuses)) {
                // non-plantilla, not re-assigned -> use EmployeeAssign values
                $assign = $assignData->get($controlNo);
                $employee->Office   = $assign->office   ?? $employee->Office;
                $employee->office2  = $assign->office2  ?? null;
                $employee->group    = $assign->group    ?? null;
                $employee->division = $assign->division ?? null;
                $employee->section  = $assign->section  ?? null;
                $employee->unit     = $assign->unit     ?? null;
            }
            // else: Regular, not re-assigned -> keep vwplantillastructure values already merged above

            return $employee;
        });

        $resource = $employees->map(
            fn($employee) => new EmployeeActiveResource($employee, $reassignedControlNos)
        );

        return $resource;
    }


    public function structure($office)
    {
        // BASE RESULT STRUCTURE
        $officeData = [
            'office' => $office,
            'office2' => []
        ];

        // GET ALL RECORDS FOR THE OFFICE FROM THE VIEW
        $plantillaUnits = vwplantillastructure::where('office', $office)->get();

        // GET ALL RECORDS FOR THE OFFICE FROM THE OUTSIDE TABLE
        $outsideUnits = OfficeStructureOutside::where('office', $office)->get();

        // COMBINE BOTH SOURCES INTO ONE COLLECTION
        $allunits = $plantillaUnits->merge($outsideUnits)
            ->sortBy([
                ['office2', 'asc'],
                ['group', 'asc'],
                ['division', 'asc'],
                ['section', 'asc'],
                ['unit', 'asc'],
            ])
            ->values();

        /* ============================================================
   1. PROCESS OFFICE2
============================================================ */

        $office2List = $allunits->unique('office2');

        foreach ($office2List as $office2Row) {

            $office2Name = $office2Row->office2 ?? null;

            $office2Data = [
                'office2' => $office2Name,
                'group' => []
            ];

            // FILTER ALL RECORDS UNDER THIS office2
            $office2units = $allunits->where('office2', $office2Name);

            /* ============================================================
       2. PROCESS group UNDER THIS office2
    ============================================================ */

            $group = $office2units->unique('group');

            foreach ($group as $groupRow) {

                $groupName = $groupRow->group ?? null;

                $groupData = [
                    'group' => $groupName,
                    'divisions' => [],
                    'sections_without_division' => [],
                    'units_without_division' => []
                ];

                // FILTER RECORDS FOR THIS GROUP
                $groupunits = $office2units->where('group', $groupName);

                /* ============================================================
           3. PROCESS divisionS UNDER THIS GROUP
        ============================================================ */
                $divisions = $groupunits->whereNotNull('division')->unique('division');

                foreach ($divisions as $division) {

                    $divisionData = [
                        'division' => $division->division,
                        'sections' => [],
                        'units_without_section' => []
                    ];

                    // sectionS UNDER THIS division
                    $sections = $groupunits
                        ->where('division', $division->division)
                        ->whereNotNull('section')
                        ->unique('section');

                    foreach ($sections as $section) {

                        $sectionData = [
                            'section' => $section->section,
                            'units' => $groupunits
                                ->where('division', $division->division)
                                ->where('section', $section->section)
                                ->whereNotNull('unit')
                                ->pluck('unit')
                                ->unique()
                                ->values()
                                ->toArray()
                        ];

                        $divisionData['sections'][] = $sectionData;
                    }

                    // unitS WITHOUT section
                    $divisionunits = $groupunits
                        ->where('division', $division->division)
                        ->whereNull('section')
                        ->whereNotNull('unit')
                        ->pluck('unit')
                        ->unique()
                        ->values()
                        ->toArray();

                    $divisionData['units_without_section'] = $divisionunits;

                    $groupData['divisions'][] = $divisionData;
                }

                /* ============================================================
           4. sectionS WITHOUT division UNDER THIS GROUP
        ============================================================ */

                $sectionsWithoutdivision = $groupunits
                    ->whereNull('division')
                    ->whereNotNull('section')
                    ->unique('section');

                foreach ($sectionsWithoutdivision as $section) {

                    $sectionData = [
                        'section' => $section->section,
                        'units' => $groupunits
                            ->whereNull('division')
                            ->where('section', $section->section)
                            ->whereNotNull('unit')
                            ->pluck('unit')
                            ->unique()
                            ->values()
                            ->toArray()
                    ];

                    $groupData['sections_without_division'][] = $sectionData;
                }

                // unitS WITHOUT division AND section
                $unitsWithoutdivision = $groupunits
                    ->whereNull('division')
                    ->whereNull('section')
                    ->whereNotNull('unit')
                    ->pluck('unit')
                    ->unique()
                    ->values()
                    ->toArray();

                $groupData['units_without_division'] = $unitsWithoutdivision;

                $office2Data['group'][] = $groupData;
            }

            $officeData['office2'][] = $office2Data;
        }

        return [$officeData];
    }
}
