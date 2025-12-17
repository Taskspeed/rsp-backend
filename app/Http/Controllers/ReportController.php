<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function generatePlantilla(Request $request)
    {
        // $rows = DB::table('vwplantillastructure')
        //     ->orderBy('office')
        //     ->orderBy('office2')
        //     ->orderBy('group')
        //     ->orderBy('division')
        //     ->orderBy('section')
        //     ->orderBy('unit')
        //     ->orderBy('ItemNo')
        //     ->get();

        $rows = DB::table('vwplantillastructure as p')
            ->leftJoin('vwActive as a', 'a.ControlNo', '=', 'p.ControlNo')
            ->select(
                'p.*',
            'a.Surname as lastname',
            'a.Firstname as firstname',
            'a.MIddlename as middlename',

            'a.Steps as step',
            'a.Birthdate as birthdate',
            'a.Steps as step',
            'p.level'
            )
            ->orderBy('p.office')
            ->orderBy('p.office2')
            ->orderBy('p.group')
            ->orderBy('p.division')
            ->orderBy('p.section')
            ->orderBy('p.unit')
            // ->orderBy('p.ItemNo')

            ->get();


        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $result = [];

        foreach ($rows->groupBy('office') as $officeName => $officeRows) {

            $officeData = [
                'office'    => $officeName,
                'employees' => [],
                'office2'   => []
            ];

            /* ================= OFFICE LEVEL ================= */
            $officeEmployees = $officeRows->filter(
                fn($r) =>
                is_null($r->office2) &&
                    is_null($r->group) &&
                    is_null($r->division) &&
                    is_null($r->section) &&
                    is_null($r->unit)
            );

            $officeData['employees'] = $officeEmployees
                ->map(fn($r) => $this->mapEmployee($r))
                ->values();

            // REMOVE office-level employees
            $remainingOfficeRows = $officeRows->reject(
                fn($r) =>
                is_null($r->office2) &&
                    is_null($r->group) &&
                    is_null($r->division) &&
                    is_null($r->section) &&
                    is_null($r->unit)
            );

            /* ================= OFFICE2 ================= */
            foreach ($remainingOfficeRows->groupBy('office2') as $office2Name => $office2Rows) {

                $office2Data = [
                    'office2'   => $office2Name,
                    'employees' => [],
                    'groups'    => []
                ];

                $office2Employees = $office2Rows->filter(
                    fn($r) =>
                    is_null($r->group) &&
                        is_null($r->division) &&
                        is_null($r->section) &&
                        is_null($r->unit)
                );

                $office2Data['employees'] = $office2Employees
                    ->map(fn($r) => $this->mapEmployee($r))
                    ->values();

                $remainingOffice2Rows = $office2Rows->reject(
                    fn($r) =>
                    is_null($r->group) &&
                        is_null($r->division) &&
                        is_null($r->section) &&
                        is_null($r->unit)
                );

                /* ================= GROUP ================= */
                foreach ($remainingOffice2Rows->groupBy('group') as $groupName => $groupRows) {

                    $groupData = [
                        'group'     => $groupName,
                        'employees' => [],
                        'divisions' => []
                    ];

                    $groupEmployees = $groupRows->filter(
                        fn($r) =>
                        is_null($r->division) &&
                            is_null($r->section) &&
                            is_null($r->unit)
                    );

                    $groupData['employees'] = $groupEmployees
                        ->map(fn($r) => $this->mapEmployee($r))
                        ->values();

                    $remainingGroupRows = $groupRows->reject(
                        fn($r) =>
                        is_null($r->division) &&
                            is_null($r->section) &&
                            is_null($r->unit)
                    );

                    /* ================= DIVISION ================= */
                    foreach ($remainingGroupRows->groupBy('division') as $divisionName => $divisionRows) {

                        $divisionData = [
                            'division'  => $divisionName,
                            'employees' => [],
                            'sections'  => []
                        ];

                        $divisionEmployees = $divisionRows->filter(
                            fn($r) =>
                            is_null($r->section) &&
                                is_null($r->unit)
                        );

                        $divisionData['employees'] = $divisionEmployees
                            ->map(fn($r) => $this->mapEmployee($r))
                            ->values();

                        $remainingDivisionRows = $divisionRows->reject(
                            fn($r) =>
                            is_null($r->section) &&
                                is_null($r->unit)
                        );

                        /* ================= SECTION ================= */
                        foreach ($remainingDivisionRows->groupBy('section') as $sectionName => $sectionRows) {

                            $sectionData = [
                                'section'   => $sectionName,
                                'employees' => [],
                                'units'     => []
                            ];

                            $sectionEmployees = $sectionRows->filter(
                                fn($r) =>
                                is_null($r->unit)
                            );

                            $sectionData['employees'] = $sectionEmployees
                                ->map(fn($r) => $this->mapEmployee($r))
                                ->values();

                            $remainingSectionRows = $sectionRows->reject(
                                fn($r) =>
                                is_null($r->unit)
                            );

                            /* ================= UNIT ================= */
                            foreach ($remainingSectionRows->groupBy('unit') as $unitName => $unitRows) {

                                $sectionData['units'][] = [
                                    'unit'      => $unitName,
                                    'employees' => $unitRows
                                        ->map(fn($r) => $this->mapEmployee($r))
                                        ->values()
                                ];
                            }

                            $divisionData['sections'][] = $sectionData;
                        }

                        $groupData['divisions'][] = $divisionData;
                    }

                    $office2Data['groups'][] = $groupData;
                }

                $officeData['office2'][] = $office2Data;
            }

            $result[] = $officeData;
        }

        return response()->json($result);
    }

    /* ================= EMPLOYEE MAPPER ================= */
    private function mapEmployee($row)
    {
        return [
          'controlNo' => $row->ControlNo,

            'itemNo'    => $row->ItemNo,
            'position'   => $row->position,
            'sg'         => $row->SG,

            'authorized' => '1,340,724.00',
            'actual' => '1,340,724.00',

            'code' => '11',
            'type' => 'C',

            'level'     => $row->level,

            'lastname' => $row->ControlNo ? $row->lastname : 'VACANT',
            'firstname' => $row->ControlNo ? $row->firstname : 'VACANT',
            'middlename' => $row->ControlNo ? $row->middlename : 'VACANT',
            'birthdate' => $row->ControlNo ? $row->birthdate : 'VACANT',


            'funded'     => $row->Funded,
            // 'name'       => $row->ControlNo ? $row->Name1 : 'VACANT',
            'status'     => $row->ControlNo ? $row->Status : 'VACANT',
            'pics'       => $row->Pics,
            // 'office'     => $row->office,
            // 'office2'    => $row->office2,
            // 'division'   => $row->division,
            // 'section'    => $row->section,
            // 'unit'       => $row->unit,
        ];
    }
}
