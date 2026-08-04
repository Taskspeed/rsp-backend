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
use Illuminate\Support\Facades\Log;

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

        // GET DISTINCT OFFICE NAMES THAT ALREADY EXIST IN vwplantillastructure
        $officePlantilla = vwplantillastructure::select('office')
            ->whereIn('office', $data->pluck('office_name'))
            ->pluck('office')
            ->unique()
            ->values();

        // ATTACH TRUE/FALSE FLAG PER OFFICE
        $data = $data->map(function ($item) use ($officePlantilla) {
            // FALSE if it already exists in plantilla, TRUE if it doesn't
            $item->structure = !$officePlantilla->contains($item->office_name);
            return $item;
        });

        return $this->successMessage($data, 'success fetch', 200);
    }

    // store 
    // store 
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'office_name' => 'required|string|unique:lib_offices,office_name'
        ]);

        $result = LibOffice::create($validatedData);

        return $this->successMessage($result, 'success created', 200);
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


    // structure store 


    // structure store 
    public function structureStore(Request $request)
    {
        $validatedData = $request->validate([
            'lib_office_id' => 'required|exists:lib_offices,id',
            'office2' => 'nullable|string',
            'group' => 'nullable|string',
            'division' => 'nullable|string',
            'section' => 'nullable|string',
            'unit' => 'nullable|string',
        ]);

        $libOffice = LibOffice::find($validatedData['lib_office_id']);

        if (!$libOffice) {
            return $this->errorMessage('Office not found.', 404);
        }


        $officeName = $libOffice->office_name;

        $existsInPlantilla = vwplantillastructure::where('office', $officeName)->exists();



        if ($existsInPlantilla) {
            return $this->errorMessage('This office already has a structure. No need to add.', 400);
        }

        $validatedData['office'] = $officeName;

        $officeStructureOutside = OfficeStructureOutside::create($validatedData);


        return $this->successMessage($officeStructureOutside, 'success created', 200);
    }

    public function structureDelete(int $structureId)
    {

        $structure = OfficeStructureOutside::find($structureId);

        if (!$structure) {
            return $this->errorMessage('Structure not found.', 404);
        }

        $structure->delete();

        return $this->successMessage($structure, 'deleted success', 200);
    }
}
