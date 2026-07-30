<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeAssignRequest;
use App\Http\Requests\EmployeeAssignStoreRequest;
use App\Http\Requests\EmployeeAssignUpdateRequest;
use App\Models\EmployeeAssign;
use App\Models\SPMS\Employee;
use App\Models\vwActive;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class EmployeeAssignController extends Controller
{
    //
    use ApiResponseTrait;

    public function indexEmployeeAssign(Request $request)
    {
        $perPage = $request->input('per_page', 15); // default 15 per page, override via ?per_page=

        $employee = EmployeeAssign::with(['xPersonal', 'vwActive'])
            ->paginate($perPage);

        if ($employee->isEmpty()) {
            return $this->successMessage($employee, 'no record employee', 200);
        }

        $employee->getCollection()->transform(function ($item) {
            return [
                'employee_assign_id' => $item->id,
                'control_no'         => $item->control_no,
                'name'               => $item->vwActive->name4 ?? null,
                'designation'        => $item->vwActive->Designation ?? null,
                'status'             => $item->vwActive->Status ?? null,
                'office'             => $item->office,
                'office2'            => $item->office2,
                'group'              => $item->group,
                'division'           => $item->division,
                'section'            => $item->section,
                'unit'               => $item->unit,
                'created_at'         => $item->created_at,
            ];
        });

        return $this->successMessage($employee, 'success fetch', 200);
    }

    public function storeEmployeeAssign(EmployeeAssignStoreRequest $request)
    {
        $validatedData = $request->validated();

        // check if the employee is already assigned
        $findEmployee = EmployeeAssign::where('control_no', $validatedData['control_no'])->first();

        if ($findEmployee) {
            return $this->errorMessage('Employee is already assigned', 409);
        }

        $employee = EmployeeAssign::create($validatedData);


        // // create or update the corresponding Employee record
        // $spmsEmployee = Employee::updateOrCreate(
        //     ['ControlNo' => $employee->control_no], // match condition
        //     [
        //         'job_title' => 'Employee',
        //         'suffix'    => null,
        //         'prefix'    => null,
        //         'rank'      => 'Employee',
        //         // 'level'
        //     ]
        // );
        return $this->successMessage($employee, 'assign employee success', 200);
    }


   public function updateEmployeeAssign(EmployeeAssignUpdateRequest $request, $controlNo)
{
    $validatedData = $request->validated();

    $findEmployee = EmployeeAssign::where('control_no', $controlNo)->first();

    if (!$findEmployee) {
        return $this->errorMessage('Employee controlNo no record', 409);
    }

    $findEmployee->update($validatedData);

    return $this->successMessage($findEmployee, 'assign employee success updated', 200);
}

public function deleteEmployeeAssign($controlNo)
{
    $findEmployee = EmployeeAssign::where('control_no', $controlNo)->first();

    if (!$findEmployee) {
        return $this->errorMessage('Employee controlNo no record', 409);
    }

    $findEmployee->delete();

    return $this->successMessage($findEmployee, 'employee assign remove success', 200);
}

   

    // view records Assignment
    public function viewEmployeeAssign(string $controlNo)
    {
        $findEmployee = vwActive::with(['employeeReAssign', 'vwActive', 'xPersonal'])
            ->where($controlNo)->get();

        if (!$findEmployee) {
            return $this->errorMessage('Employee controlNo no record', 409);
        }

        $employee = [
            'employee_assign_id' => $findEmployee->id,
            'control_no'         => $findEmployee->control_no,
            'Surname'            => $findEmployee->xPersonal->Surname ?? null,
            'Firstname'          => $findEmployee->xPersonal->Firstname ?? null,
            'designation'        => $findEmployee->vwActive->Designation ?? null,
            'status'             => $findEmployee->vwActive->Status ?? null,
            'office'             => $findEmployee->office,
            'office2'            => $findEmployee->office2,
            'group'              => $findEmployee->group,
            'division'           => $findEmployee->division,
            'section'            => $findEmployee->section,
            'unit'               => $findEmployee->unit,
            'created_at'         => $findEmployee->created_at,
            're_assignment_history'  => $findEmployee->employeeReAssign->map(function ($reAssign) {
                return [
                    'employee_reassign_id' => $reAssign->id,
                    'control_no'           => $reAssign->control_no,
                    'office'               => $reAssign->office,
                    'office2'              => $reAssign->office2,
                    'group'                => $reAssign->group,
                    'division'             => $reAssign->division,
                    'section'              => $reAssign->section,
                    'unit'                 => $reAssign->unit,
                    're_assign_date'       => $reAssign->re_assign_date,
                    'active'               => $reAssign->active,
                    'created_at'           => $reAssign->created_at,
                    // 'updated_at'           => $reAssign->updated_at,
                ];
            }),
        ];

        return $this->successMessage($employee, 'Assign employee detail', 200);
    }
}
