<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeUpdateCredentialsRequest;
use App\Models\Submission;
use App\Models\vwplantillastructure;
use App\Models\xPersonal;
use App\Services\EmployeeService;

use Carbon\Carbon;
use function Laravel\Prompts\table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    //

    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function appliedEmployee($ControlNo)
    {
        // Get all submissions of employee using ControlNo
        $employeeApplications = Submission::with('jobPost')
            ->where('ControlNo', $ControlNo)
            ->get();

        return response()->json([
            'data' => $employeeApplications->map(function ($submission) {
                return [
                    'submission_id' => $submission->id,
                    'status' => $submission->is_emailed  // added condition to fetch that status of the applicant email first before the status
                        ? $submission->status
                        : 'Pending',
                    'position'      => $submission->jobPost->Position ?? null,
                    'office'        => $submission->jobPost->Office ?? null,
                    'post_date' => Carbon::parse($submission->jobPost->post_date)->format('F d, Y') ?? null,
                    'end_date'     => Carbon::parse($submission->jobPost->end_date)->format('F d, Y') ?? null,
                    'applied_at'    => $submission->created_at,
                ];
            })
        ]);
    }

    //update tempreg and xservice and xpersonal  of the employee
    public function updateEmployeeCredentials(EmployeeUpdateCredentialsRequest $request, $ControlNo)
    {

        $validated = $request->validated();

        $result = $this->employeeService->updateCredentials($ControlNo, $validated);

        return $result;
    }

    //getting the image for the employee using the control number and the path stored in the database and return it as a response
    public function proxyImageInternal($ControlNo)
    {

        $data = $this->employeeService->getEmployeePhoto($ControlNo);

        return $data;
    }

    // get the wes of applicant internal
    public function workExperienceSheet($controlNo)
    {

        $data = $this->employeeService->getWESInterApplicant($controlNo);

        return $data;
    }
}
