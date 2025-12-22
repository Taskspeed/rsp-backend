<?php

namespace App\Http\Controllers;

use App\Models\JobBatchesRsp;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\FuncCall;

class ReportController extends Controller
{

    // Generate Report on plantilla Structure...
    public function generatePlantilla(Request $request)
    {
        // $rows = DB::table('vwplantillastructure as p')
        //     ->leftJoin('vwActive as a', 'a.ControlNo', '=', 'p.ControlNo')
        //     ->leftJoin('vwofficearrangement as o', 'o.Office', '=', 'p.office')
        //     ->leftJoin('xService as s', 's.ControlNo', '=', 'p.ControlNo')->select('PMID')->lastest()->limit(1)
        //     ->select(
        //         'p.*',
        //         'a.Status as status',
        //         'a.Steps as steps',
        //         'a.Birthdate as birthdate',
        //         'a.Surname as lastname',
        //         'a.Firstname as firstname',
        //         'a.MIddlename as middlename',
        //     // 'a.MIddlename as Gr',
        //         'p.SG as salarygrade',
        //         'p.level',
        //         'o.office_sort',
        //         's.RateYear'
        //     )

        // authorized salary



        // actual salary
        $latestXService = DB::table('xService')
            ->select('ControlNo', DB::raw('MAX(PMID) as latest_pmid'))
            ->groupBy('ControlNo');


        $rows = DB::table('vwplantillastructure as p')
            ->leftJoin('vwActive as a', 'a.ControlNo', '=', 'p.ControlNo')
            ->leftJoin('vwofficearrangement as o', 'o.Office', '=', 'p.office')

            // join latest PMID per employee
            ->leftJoinSub($latestXService, 'lx', function ($join) {
                $join->on('lx.ControlNo', '=', 'p.ControlNo');
            })

            // join xService using latest PMID
            ->leftJoin('xService as s', 's.PMID', '=', 'lx.latest_pmid')

            ->select(
                'p.*',
                'a.Status as status',
                'a.Steps as steps',
                'a.Birthdate as birthdate',
                'a.Surname as lastname',
                'a.Firstname as firstname',
                'a.MIddlename as middlename',
                'p.SG as salarygrade',
                'p.level',
                'o.office_sort',
                's.RateYear as rateyear' // ✅ correct RateYear
            )
            ->orderBy('o.office_sort')
            ->orderBy('p.office2')
            ->orderBy('p.group')
            ->orderBy('p.division')
            ->orderBy('p.section')
            ->orderBy('p.unit')
            ->orderBy('p.ItemNo')
            ->get();


        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $allControlNos = $rows->pluck('ControlNo')->filter()->unique()->values();

        $xServices = DB::table('xService')
            ->whereIn('ControlNo', $allControlNos)
            ->select('ControlNo', 'Status', 'Steps', 'FromDate', 'ToDate', 'Designation', 'SepCause', 'Grades')
            ->get();

        $xServiceByControl = $xServices->groupBy('ControlNo');

        $result = [];

        foreach ($rows->groupBy('office') as $officeName => $officeRows) {
            $officeSort = $officeRows->first()->office_sort;
            $officeLevel = $officeRows->first()->level;

            $officeData = [
                'office'      => $officeName,
                'level'       => $officeLevel,
                'office_sort' => $officeSort,
                'employees'   => [],
                'office2'     => []
            ];

            $officeEmployees = $officeRows->filter(
                fn($r) =>
                is_null($r->office2) &&
                is_null($r->group) &&
                is_null($r->division) &&
                is_null($r->section) &&
                is_null($r->unit)
            );
            $officeData['employees'] = $officeEmployees
                ->sortBy('ItemNo')
                // ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))
                ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))

                ->values();

            $remainingOfficeRows = $officeRows->reject(
                fn($r) =>
                is_null($r->office2) &&
                is_null($r->group) &&
                is_null($r->division) &&
                is_null($r->section) &&
                is_null($r->unit)
            );

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
                    ->sortBy('ItemNo')
                    // ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))
                    ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))

                    ->values();

                $remainingOffice2Rows = $office2Rows->reject(
                    fn($r) =>
                    is_null($r->group) &&
                    is_null($r->division) &&
                    is_null($r->section) &&
                    is_null($r->unit)
                );

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
                        ->sortBy('ItemNo')
                        // ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))
                        ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))

                        ->values();

                    $remainingGroupRows = $groupRows->reject(
                        fn($r) =>
                        is_null($r->division) &&
                        is_null($r->section) &&
                        is_null($r->unit)
                    );

                    // ----- SORT HERE by divordr -----
                    foreach ($remainingGroupRows->sortBy('divordr')->groupBy('division') as $divisionName => $divisionRows) {
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
                            ->sortBy('ItemNo')
                            // ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))
                            ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))

                            ->values();

                        $remainingDivisionRows = $divisionRows->reject(
                            fn($r) =>
                            is_null($r->section) &&
                            is_null($r->unit)
                        );

                        // ----- SORT HERE by secordr -----
                        foreach ($remainingDivisionRows->sortBy('secordr')->groupBy('section') as $sectionName => $sectionRows) {
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
                                ->sortBy('ItemNo')
                                // ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))
                                ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))

                                ->values();

                            $remainingSectionRows = $sectionRows->reject(
                                fn($r) =>
                                is_null($r->unit)
                            );

                            // ----- SORT HERE by unitordr -----
                            foreach ($remainingSectionRows->sortBy('unitordr')->groupBy('unit') as $unitName => $unitRows) {
                                $sectionData['units'][] = [
                                    'unit'      => $unitName,
                                    'employees' => $unitRows
                                        ->sortBy('ItemNo')
                                        // ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))
                                        ->map(fn($r) => $this->mapEmployee($r, $xServiceByControl))

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

        $result = collect($result)->sortBy('office_sort')->values()->all();

        return response()->json($result);
    }

    private function mapEmployee($row, $xServiceByControl,)

    {
        $controlNo = $row->ControlNo;
        $status = $row->status;






        $dateOriginalAppointed = null;
        $dateLastPromotion = null;

        if ($controlNo && isset($xServiceByControl[$controlNo])) {
            $xList = $xServiceByControl[$controlNo]
                ->filter(fn ($svc) => $svc->Status == $status)
                ->sortBy('FromDate')
                ->values();

            if ($xList->count()) {
                if (strtolower($status) === 'regular') {
                    $first = $xList->first();
                    $designation = $first->Designation ?? null;

                    $resignedRows = $xList->filter(function ($svc) use ($designation) {
                        return (
                            ($svc->Designation ?? null) == $designation
                            && isset($svc->SepCause)
                            && strtolower(trim($svc->SepCause)) === 'resigned'
                        );
                    });

                    if ($resignedRows->count()) {
                        $resignedToDate = $resignedRows->sortByDesc('ToDate')->first()->ToDate;
                        $nextRow = $xList
                            ->filter(fn ($svc) => strtotime($svc->FromDate) > strtotime($resignedToDate))
                            ->sortBy(fn ($svc) => strtotime($svc->FromDate) - strtotime($resignedToDate))
                            ->first();
                        $dateOriginalAppointed = $nextRow ? $nextRow->FromDate : null;
                    } else {
                        $dateOriginalAppointed = $first->FromDate;
                    }
                } else {
                    $dateOriginalAppointed = $xList->last()->FromDate;
                }

                // Promotion logic (numeric, non-strict grades)
                $numericGrades = $xList->pluck('Grades')->filter(function($g) {
                    return is_numeric($g);
                })->map(function($g) {
                    return (float)$g;
                });

                $highestGrade = $numericGrades->max();

                // Appointed Grades
                $appointedRow = $xList->first(fn($svc) => $svc->FromDate == $dateOriginalAppointed);
                $initialGrades = !is_null($appointedRow) ? $appointedRow->Grades : ($row->Grades ?? null);

                // Log for debugging
                logger([
                    // 'xactiveGrades' => $row->Grades,
                    'appointedRowGrades' => isset($appointedRow) ? $appointedRow->Grades : null,
                    'initialGrades' => $initialGrades,
                    'highestGrade' => $highestGrade,
                    'all xService Grades' => $xList->pluck('Grades'),
                    'numericGrades' => $numericGrades,
                    'dateOriginalAppointed' => $dateOriginalAppointed,
                ]);

                if (!is_null($dateOriginalAppointed) && !is_null($highestGrade) && !is_null($initialGrades)) {
                    // if current/initial grade is greater than or equal to highest, there is no promotion
                    if ($initialGrades >= $highestGrade) {
                        $dateLastPromotion = null;
                    } else {
                        $promotionRows = $xList
                            ->filter(fn($svc) => $svc->Grades == $highestGrade)
                            ->sortBy('FromDate')
                            ->values();

                        $dateLastPromotion = $promotionRows->count() ? $promotionRows->first()->FromDate : null;
                    }
                }
            }
        }


        // ===============================
        // VACANT → FORCE ZERO
        // ===============================
        if (is_null($controlNo)) {
            return [
                'controlNo'   => null,
                'Ordr'        => $row->Ordr,
                'itemNo'      => $row->ItemNo,
                'position'    => $row->position,
                'salarygrade' => $row->salarygrade,
                'authorized'  => '0.00',
                'actual'      => '0.00',
                'step'        => '1',
                'code'        => '11',
                'type'        => 'C',
                'level'       => $row->level,
                'lastname'    => 'VACANT',
                'firstname'   => '',
                'middlename'  => '',
                'birthdate'   => '',
                'funded'      => $row->Funded,
                'status'      => 'VACANT',
                'dateOriginalAppointed' => null,
                'dateLastPromotion'     => null,
            ];
        }

        // ===============================
        // AUTHORIZED SALARY (ANNUAL)
        // ===============================
        $salaryGrade = $row->salarygrade;
        $monthlySalary = 0;

        if (!is_null($salaryGrade)) {
            $monthlySalary = DB::table('tblSalarySchedule')
                ->where('Grade', $salaryGrade)
                ->where('Steps', 1) // forced Step 1
                ->value('Salary') ?? 0;
        }

        $authorizedAnnual = $monthlySalary * 12;
        $authorizedSalaryFormatted = number_format($authorizedAnnual, 2);

        // ===============================
        // ACTUAL SALARY (ANNUAL)
        // ===============================
        $actual = number_format($row->rateyear ?? 0, 2);

        // ===============================
        // RETURN (FILLED POSITION)
        // ===============================
        return [
            'controlNo'   => $controlNo,
            'Ordr'        => $row->Ordr,
            'itemNo'      => $row->ItemNo,
            'position'    => $row->position,
            'salarygrade' => $row->salarygrade,
            'authorized'  => $authorizedSalaryFormatted,
            'actual'      => $actual,
            'step'        => $row->steps ?? '1',
            'code'        => '11',
            'type'        => 'C',
            'level'       => $row->level,
            'lastname'    => $row->lastname,
            'firstname'   => $row->firstname,
            'middlename'  => $row->middlename,
            'birthdate'   => $row->birthdate,
            'funded'      => $row->Funded,
            'status'      => $row->Status,
            'dateOriginalAppointed' => $dateOriginalAppointed ?? null,
            'dateLastPromotion'     => $dateLastPromotion ?? null,
        ];
    }

    // get all job post
    public function getJobPost(){

        $job = JobBatchesRsp::select('id','Office','Position','post_date','end_date')->get();

        return response()->json($job);


    }


    // report job post with applicant
    public function getApplicantJobPost($jobpostId)
    {
        $jobs = Submission::where('job_batches_rsp_id', $jobpostId)
            ->with('nPersonalInfo:id,firstname,lastname')
            ->get()
            ->map(function ($item) {
                return [
                    'n_personal_info' => $item->nPersonalInfo,

                    'id' => $item->id,
                    // 'nPersonalInfo_id' => $item->nPersonalInfo_id,
                    // 'ControlNo' => $item->ControlNo,
                    // 'job_batches_rsp_id' => $item->job_batches_rsp_id,
                    // 'education_remark' => $item->education_remark,
                    // 'experience_remark' => $item->experience_remark,
                    // 'training_remark' => $item->training_remark,
                    // 'eligibility_remark' => $item->eligibility_remark,
                    // 'total_qs' => $item->total_qs,
                    // 'grand_total' => $item->grand_total,
                    // 'ranking' => $item->ranking,
                    'status' => $item->status,
                    'submitted' => $item->submitted,
                    // 'created_at' => $item->created_at,
                    // 'updated_at' => $item->updated_at,
                    // 'education_qualification' => $item->education_qualification,
                    // 'experience_qualification' => $item->experience_qualification,
                    // 'training_qualification' => $item->training_qualification,
                    // 'eligibility_qualification' => $item->eligibility_qualification,
                ];
            });

        return response()->json($jobs);
    }



    // report job post with applicant have schedules
    public function getApplicantHaveSchedules($jobpostId)
    {
        $jobs = Submission::where('job_batches_rsp_id', $jobpostId)
            ->with('schedules:id,submission_id,batch_name,full_name')
            ->get()
            ->map(function ($item) {

                $schedule = $item->schedules->first(); // if hasMany

                return [
                    'id' => $item->id,
                    'ApplicantHaveSchedules' => $item->schedules,

                    'batch_name' => $schedule?->batch_name,
                    'full_name' => $schedule?->full_name,
                ];
            });

        return response()->json($jobs);
    }
}
