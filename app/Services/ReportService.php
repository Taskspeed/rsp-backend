<?php

namespace App\Services;

use App\Jobs\GeneratePlantillaReportJob;
use App\Jobs\QueueWorkerTestJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class ReportService
{
    /**
     * Create a new class instance.
     */
    // public function __construct()
    // {
    //     //
    // }


    // Generate Report on plantilla Structure...
    public function dbm()
    {
        $eligibility = DB::table('xCivilService')
            ->select(
                'ControlNo',
                DB::raw("STRING_AGG(CivilServe, ', ') as eligibility")
            )
            ->groupBy('ControlNo');

        // actual salary
        $latestXService = DB::table('xService')
            ->select('ControlNo', DB::raw('MAX(PMID) as latest_pmid'))
            ->groupBy('ControlNo');


        $rows = DB::table('vwplantillastructure as p')
            ->leftJoin('vwActive as a', 'a.ControlNo', '=', 'p.ControlNo')
            ->leftJoin('vwofficearrangement as o', 'o.Office', '=', 'p.office')

            // join ALL eligibilities
            ->leftJoinSub($eligibility, 'eligibility', function ($join) {
                $join->on('eligibility.ControlNo', '=', 'p.ControlNo');
            })


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
                's.RateYear as rateyear', // ✅ correct RateYear
                'eligibility.eligibility'
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
                ->map(fn($r) => $this->mapEmployeeDbm($r, $xServiceByControl))

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
                    ->map(fn($r) => $this->mapEmployeeDbm($r, $xServiceByControl))

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
                        ->map(fn($r) => $this->mapEmployeeDbm($r, $xServiceByControl))

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
                            ->map(fn($r) => $this->mapEmployeeDbm($r, $xServiceByControl))

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
                                ->map(fn($r) => $this->mapEmployeeDbm($r, $xServiceByControl))

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
                                        ->map(fn($r) => $this->mapEmployeeDbm($r, $xServiceByControl))

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

    // Update the mapEmployeeDbm function call
    private function mapEmployeeDbm($row, $xServiceByControl)
    {
        $controlNo = $row->ControlNo;
        $status = $row->status;

        $dateOriginalAppointed = null;
        $dateLastPromotion = null;

        if ($controlNo && isset($xServiceByControl[$controlNo])) {
            $xList = $xServiceByControl[$controlNo]
                ->filter(fn($svc) => $svc->Status == $status)
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
                            ->filter(fn($svc) => strtotime($svc->FromDate) > strtotime($resignedToDate))
                            ->sortBy(fn($svc) => strtotime($svc->FromDate) - strtotime($resignedToDate))
                            ->first();
                        $dateOriginalAppointed = $nextRow ? $nextRow->FromDate : null;
                    } else {
                        $dateOriginalAppointed = $first->FromDate;
                    }
                } else {
                    $dateOriginalAppointed = $xList->last()->FromDate;
                }

                // Promotion logic
                $numericGrades = $xList->pluck('Grades')->filter(function ($g) {
                    return is_numeric($g);
                })->map(function ($g) {
                    return (float)$g;
                });

                $highestGrade = $numericGrades->max();
                $appointedRow = $xList->first(fn($svc) => $svc->FromDate == $dateOriginalAppointed);
                $initialGrades = !is_null($appointedRow) ? $appointedRow->Grades : ($row->Grades ?? null);

                if (!is_null($dateOriginalAppointed) && !is_null($highestGrade) && !is_null($initialGrades)) {
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

        // VACANT → FORCE ZERO
        if (is_null($controlNo)) {
            return [
                'controlNo'   => null,
                'Ordr'        => $row->Ordr,
                'itemNo'      => $row->ItemNo,
                'position'    => $row->position,
                'lastname'    => 'VACANT',
                'firstname'   => '',
                'middlename'  => '',
                'birthdate'   => '',
                'currentYearSalaryGrade' => $row->salarygrade,
                'currentYearAmount'      => '0.00',
                'currentYearStep'        => '1',
                'budgetYearSalaryGrade'  => $row->salarygrade,
                'budgetYearAmount'       => '0.00',
                'budgetYearStep'         => '1',
                'increaseDescrease'      => '0.00',
                'dateOriginalAppointed' => null,
                'dateLastPromotion'     => null,
                'status'      => 'VACANT',
            ];
        }

        // X-SERVICE DATA
        $xList = collect();
        if (isset($xServiceByControl[$controlNo])) {
            $xList = $xServiceByControl[$controlNo];
        }

        // CURRENT STEP
        $currentStep = (int) ($row->steps ?? 1);

        // CURRENT POSITION
        $currentPosition = $row->position;

        // COMPUTE BUDGET YEAR STEP (Pass current position)
        $budgetYearStep = $this->computeBudgetYearStep($xList, $currentStep, $currentPosition);

        // CURRENT YEAR AMOUNT
        $currentYearAmount = number_format($row->rateyear ?? 0, 2);

        // BUDGET YEAR AMOUNT
        $budgetMonthly = DB::table('tblSalarySchedule')
            ->where('Grade', $row->salarygrade)
            ->where('Steps', $budgetYearStep)
            ->value('Salary');

        $budgetYearAmount = $budgetMonthly
            ? number_format($budgetMonthly * 12, 2)
            : '0.00';

        $currentAnnualRaw = (float) str_replace(',', '', $currentYearAmount);
        $budgetAnnualRaw  = (float) str_replace(',', '', $budgetYearAmount);
        $increaseDecrease = $budgetAnnualRaw - $currentAnnualRaw;

        // RETURN DATA
        return [
            'controlNo'   => $controlNo,
            'Ordr'        => $row->Ordr,
            'itemNo'      => $row->ItemNo,
            'position'    => $row->position,
            'lastname'    => $row->lastname,
            'firstname'   => $row->firstname,
            'middlename'  => $row->middlename,
            'birthdate'   => $row->birthdate,
            'currentYearSalaryGrade' => $row->salarygrade,
            'currentYearAmount'      => $currentYearAmount,
            'currentYearStep'        => $currentStep,
            'budgetYearSalaryGrade'  => $row->salarygrade,
            'budgetYearAmount'       => $budgetYearAmount,
            'budgetYearStep'         => $budgetYearStep,
            'increaseDescrease' => number_format($increaseDecrease, 2),
            'dateOriginalAppointed' => $dateOriginalAppointed ?? null,
            'dateLastPromotion'     => $dateLastPromotion ?? null,
            'eligibility' => $row->eligibility,
            'status'      => $row->status,
        ];
    }


    private function computeBudgetYearStep($xList, $currentStep)
    {
        $allowedStatuses = ['regular', 'elective', 'co-terminous'];

        if ($xList->isEmpty()) {
            return $currentStep;
        }

        // newest first
        $xList = $xList->sortByDesc('FromDate')->values();

        // current active service
        $currentService = $xList->first(function ($svc) use ($allowedStatuses) {
            return in_array(strtolower($svc->Status), $allowedStatuses)
                && !empty($svc->Designation);
        });

        if (!$currentService) {
            return $currentStep;
        }

        $designation = $currentService->Designation;
        $step = (int) ($currentService->Steps ?? $currentStep);

        // SAME position + SAME step only
        $samePosSameStep = $xList->filter(function ($svc) use (
            $designation,
            $step,
            $allowedStatuses
        ) {
            return $svc->Designation === $designation
                && (int)($svc->Steps ?? 0) === $step
                && in_array(strtolower($svc->Status), $allowedStatuses);
        })->sortBy('FromDate')->values();

        if ($samePosSameStep->isEmpty()) {
            return $currentStep;
        }

        // earliest SAME position + SAME step
        $startYear = (int) Carbon::parse($samePosSameStep->first()->FromDate)->year;

        // 🔥 CURRENT YEAR (not budget param)
        $currentYear = (int) Carbon::now()->year;

        // inclusive count (ex: 2024–2026 = 3)
        $yearsRendered = ($currentYear - $startYear) + 1;

        // DBM rule: after 3 years → +1 step
        if ($yearsRendered >= 3) {
            return $currentStep + 1;
        }

        return $currentStep;
    }

    // generate report plantilla
    public function plantilla()
    {

        // ✅ Check if queue worker is running BEFORE dispatching
        if (!$this->isQueueWorkerRunning()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Queue worker is not running. Please contact Deniel Tomenio for this issue.'
            ], 503);
        }


        $jobId = Str::uuid()->toString();

        // Initialize job status
        Cache::put("plantilla_job_{$jobId}", [
            'status' => 'queued',
            'progress' => 0
        ], 600); // 10 minutes TTL

        // Dispatch the job
        GeneratePlantillaReportJob::dispatch($jobId)->onQueue('reports');

        return response()->json([
            'job_id' => $jobId,
            'status' => 'queued'
        ]);
    }


    private function isQueueWorkerRunning()
    {
        try {
            // Method 1: Check if we can connect to queue
            $connection = config('queue.default');
            $queueName = config("queue.connections.{$connection}.queue", 'default');

            // Try to get queue size (this will fail if Redis/database is not available)
            Queue::size($queueName);

            // Method 2: Check for active workers by trying to dispatch a test
            // This is optional but more reliable
            return $this->checkForActiveWorkers();
        } catch (\Exception $e) {
            Log::error('Queue check failed: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Check if there are active queue workers by looking at recent job processing
     */
    private function checkForActiveWorkers()
    {
        // Create a test marker
        $testKey = 'queue_worker_test_' . now()->timestamp;
        Cache::put($testKey, 'waiting', 10); // 10 seconds TTL

        // Dispatch a test job
        QueueWorkerTestJob::dispatch($testKey)->onQueue('reports');

        // Wait a moment and check if job was processed
        sleep(2);

        $result = Cache::get($testKey);

        // If the test job ran, it would have changed this value
        return $result === 'processed';
    }

    public function checkQueueWorkerStatus()
    {
        $isRunning = $this->isQueueWorkerRunning();

        return response()->json([
            'is_running' => $isRunning,
            'message' => $isRunning
                ? 'Queue worker is running'
                : 'Queue worker is not running'
        ]);
    }

    // plantilla status
    public function status($jobId)
    {
        $status = Cache::get("plantilla_job_{$jobId}");

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }

    // cancel generate report
    public function cancel($jobId)
    {
        $status = Cache::get("plantilla_job_{$jobId}");

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Update status to cancelled
        Cache::put("plantilla_job_{$jobId}", [
            'status' => 'cancelled',
            'progress' => $status['progress'] ?? 0
        ], 600);

        return response()->json([
            'status' => 'cancelled',
            'message' => 'Job cancellation requested'
        ]);
    }
}
