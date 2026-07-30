<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeAssignRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\EmployeeAssign;
use App\Models\EmployeeReAssign;
use App\Models\LibOffice;
use App\Models\OfficeStructureOutside;
use App\Models\vwActive;
use App\Models\vwplantillastructure;
use App\Services\OfficeService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    //

    use ApiResponseTrait;

    protected OfficeService $officeService;

    public function __construct(OfficeService $officeService)
    {
        $this->officeService = $officeService;
    }

    // get the employee under of the office
    public function getEmployee(string $office)
    {
        $data = $this->officeService->employee($office);

        return $this->successMessage($data, 'success', 200);
    }

    // get active Employee
    public function getEmployeeActive(string $office)
    {
        $data = $this->officeService->employeeListActive($office);

        return $this->successMessage($data, 'success', 200);
    }

    // fetch employee only JOB ORDER, CASUAL,CONTRACTUAl,HONORARIUM
    public function contractualEmployee(string $office)
    {

        $assignedControlNos = EmployeeAssign::pluck('control_no');

        $data = vwActive::select('ControlNo', 'Office', 'Designation', 'Status', 'Name4')
            ->where('Office', $office)
            ->whereIn('Status', ['CONTRACTUAL', 'CASUAL'])
            ->whereNotIn('ControlNo', $assignedControlNos)
            ->get();


        return $this->successMessage($data, 'success', 200);
    }

    public function employeeWithReAssign(string $office)
    {

        $employee = EmployeeReAssign::where('office', $office)->where('active', 1)->get();
        return response(
            $employee
        );
    }

    public function officeStructure(string $office)
    {

        $data = $this->officeService->structure($office);

        return $this->successMessage($data, 'success fetch structure', 200);
    }

    //view 
    public function view(int $officeId)
    {

        $data = LibOffice::with('officeStructureOutside')->find($officeId);

        if (!$data) {
            return $this->successMessage($data, 'no record found ', 200);
        }

        return $this->successMessage($data, 'success fetch', 200);
    }


    // fetch
    public function index()
    {
        $data = LibOffice::with('officeStructureOutside')
            ->select('id', 'office_name', 'created_at')
            ->get()
            ->map(function ($item) {
                $item->officeId = $item->id;
                unset($item->id);

                $item->officeStructureOutside->transform(function ($structure) {
                    $structure->structureId = $structure->id;
                    unset($structure->id);
                    return $structure;
                });

                return $item;
            });

        if ($data->isEmpty()) {
            return $this->successMessage($data, 'no record found', 200);
        }

        return $this->successMessage($data, 'success fetch', 200);
    }

    // store 
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'office_name' => 'required|string',
            'office2' => 'nullable|string',
            'group' => 'nullable|string',
            'division' => 'nullable|string',
            'section' => 'nullable|string',
            'unit' => 'nullable|string',
        ]);

        $result = LibOffice::updateOrCreate(
            ['office_name' => $validatedData['office_name']],
        );

        // CHECK IF THIS OFFICE ALREADY HAS A STRUCTURE IN vwplantillastructure
        $existsInPlantilla = vwplantillastructure::where('office', $validatedData['office_name'])->exists();

        $structureFields = [
            'office2' => $validatedData['office2'] ?? null,
            'group' => $validatedData['group'] ?? null,
            'division' => $validatedData['division'] ?? null,
            'section' => $validatedData['section'] ?? null,
            'unit' => $validatedData['unit'] ?? null,
        ];

        $officeStructureOutside = null;

        // only create if office is NOT already in vwplantillastructure
        // AND at least one structure field has a real value
        if (!$existsInPlantilla && array_filter($structureFields, fn($value) => !is_null($value) && $value !== '')) {
            $officeStructureOutside = OfficeStructureOutside::create(array_merge(
                [
                    'lib_office_id' => $result->id,
                    'office' => $validatedData['office_name'] ?? null,
                ],
                $structureFields
            ));
        }

        return $this->successMessage([$result, $officeStructureOutside], 'success created', 200);
    }

    // update
    public function update(Request $request, int $officeId)
    {
        $office = LibOffice::find($officeId);

        if (!$office) {
            return $this->errorMessage('officeId are not found', 404);
        }

        $validatedData = $request->validate([
            'office_name' => 'required|string|unique:lib_offices,office_name,' . $officeId . ',id'
        ]);

        $office->update($validatedData);

        //auto update the office name in the structure if it exists
        $structure = OfficeStructureOutside::where('lib_office_id', $officeId)->get();

        if ($structure->isNotEmpty()) {
            foreach ($structure as $s) {
                $s->update(['office' => $validatedData['office_name']]);
            }
        }

        return $this->successMessage($office, 'success updated', 200);
    }

    // delete
    public function destroy(int $officeId)
    {

        $office = LibOffice::find($officeId);

        if (!$office) {
            return $this->errorMessage('officeId are not found', 404);
        }

        $office->delete();

        return $this->successMessage($office, 'deleted success', 200);
    }

    // update structure
    public function updateStructure(Request $request, int $structureId)
    {
        $structure = OfficeStructureOutside::find($structureId);

        if (!$structure) {
            return $this->errorMessage('structureId are not found', 404);
        }

        $validatedData = $request->validate([
            'office2' => 'nullable|string',
            'group' => 'nullable|string',
            'division' => 'nullable|string',
            'section' => 'nullable|string',
            'unit' => 'nullable|string',
        ]);

        $structure->update($validatedData);

        return $this->successMessage($structure, 'success updated', 200);
    }
}
