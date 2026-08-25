<?php

namespace App\Services;

use App\Models\vwplantillastructure;
use App\Models\vwPlantillaStructureAsOf;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;

class PlantillaService
{
    /**
     * Create a new class instance.
     */
    // public function __construct()
    // {
    //     //
    // }

    // fetch all employee on the plantilla
    public function employeePublication($request)
    {

        // get the request args office name
        $query = vwplantillastructure::select([
            'vwplantillaStructure.ControlNo',
            'vwplantillaStructure.ID',
            'vwplantillaStructure.office',
            'vwplantillaStructure.office2',
            'vwplantillaStructure.group',
            'vwplantillaStructure.division',
            'vwplantillaStructure.section',
            'vwplantillaStructure.unit',
            'vwplantillaStructure.position',
            'vwplantillaStructure.PositionID',
            'vwplantillaStructure.PageNo',
            'vwplantillaStructure.ItemNo',
            'vwplantillaStructure.SG',
            'vwplantillaStructure.Funded',
            'vwplantillaStructure.level',
            'vwplantillaStructure.Name1',
            'vwplantillaStructure.Pics',
            'vwplantillaStructure.Status as plantillaStatus',
            'vwplantillaStructure.Name4',
            'vwplantillaStructure.OfficeID',
            'vwActive.BirthDate',
            'vwActive.Designation',
            'yDesignation.Status as designationStatus',
            'yDesignation.PMID as designationPositionId',

        ])
            ->leftJoin('vwActive', 'vwplantillaStructure.ControlNo', '=', 'vwActive.ControlNo')
            ->leftJoin('yDesignation', 'vwplantillaStructure.PositionID', '=', 'yDesignation.PMID')

            ->distinct();

        // Filter by office if provided: /plantilla?office=OfficeName
        if ($office = $request->query('office')) {
            $query->where('vwplantillaStructure.office', $office);
        }

        $plantilla = $query->get();

        return $plantilla;
    }


    // fetch employee on the plantilla by office (with optional as-of date range)
    public function getEmployeeByOffice(Request $request)
    {
        $office = $request->input('office');
        $as_of_date = $request->input('date');


        if ($as_of_date) {
            try {
                $as_of_date = Carbon::parse($as_of_date)->format('Y-m-d');
            } catch (InvalidFormatException $e) {
                return response()->json(['message' => 'Invalid date format. use Y-m-d'], 400);
            }
        }

        if (!$office) {
            return response()->json(['message' => 'No office specified.'], 400);
        }

        // If date is provided, use the historical "as of" view
        // If date is provided, use the historical "as of" view
        if ($as_of_date) {
            $rows = VwPlantillaStructureAsOf::select([
                'vwPlantillaStructureAsOf.ControlNo',
                'vwPlantillaStructureAsOf.ID',
                'vwPlantillaStructureAsOf.office',
                'vwPlantillaStructureAsOf.office2',
                'vwPlantillaStructureAsOf.group',
                'vwPlantillaStructureAsOf.division',
                'vwPlantillaStructureAsOf.section',
                'vwPlantillaStructureAsOf.unit',
                'vwPlantillaStructureAsOf.position',
                'vwPlantillaStructureAsOf.PositionID',
                'vwPlantillaStructureAsOf.PageNo',
                'vwPlantillaStructureAsOf.ItemNo',
                'vwPlantillaStructureAsOf.SG',
                'vwPlantillaStructureAsOf.Funded',
                'vwPlantillaStructureAsOf.level',
                'vwPlantillaStructureAsOf.Name1',
                'vwPlantillaStructureAsOf.Pics',
                'vwPlantillaStructureAsOf.Status as plantillaStatus',
                'vwPlantillaStructureAsOf.Name4',
                'vwPlantillaStructureAsOf.OfficeID',
                'vwPlantillaStructureAsOf.FromDate',
                'vwPlantillaStructureAsOf.ToDate',
                'yDesignation.Status as designationStatus',
                'yDesignation.PMID as designationPositionId',
            ])
                ->leftJoin('yDesignation', 'vwPlantillaStructureAsOf.PositionID', '=', 'yDesignation.PMID')
                ->where('vwPlantillaStructureAsOf.office', $office)
                // NOTE: no more filtering by date here — kunin lahat ng candidate rows
                // (vacant + every historical/current occupant match) per position slot,
                // tapos i-resolve natin sa PHP kung sino ang dapat lumabas sa date na yun.
                ->get();

            $query = $rows
                ->groupBy('ID') // isang group per position slot
                ->map(function ($group) use ($as_of_date) {
                    // Hanapin yung row na tumutugma sa as-of date (may tao na active nun)
                    $active = $group->first(function ($row) use ($as_of_date) {
                        if (is_null($row->ControlNo)) {
                            return false; // vacant row, skip muna, baka may match pa
                        }
                        if (is_null($row->FromDate) || is_null($row->ToDate)) {
                            return false; // walang date info (e.g. CASUAL na di kasama sa view) - treat as no match
                        }
                        return $as_of_date >= Carbon::parse($row->FromDate)->format('Y-m-d')
                            && $as_of_date <= Carbon::parse($row->ToDate)->format('Y-m-d');
                    });

                    if ($active) {
                        return $active;
                    }

                    // Walang match sa date na ito - ibalik yung position pero i-null
                    // ang lahat ng person-related fields, parang vacant.
                    $template = $group->first();
                    $vacant = clone $template;
                    $vacant->ControlNo = null;
                    $vacant->Name1 = null;
                    $vacant->plantillaStatus = null;
                    $vacant->Name4 = null;
                    $vacant->Pics = null;
                    $vacant->FromDate = null;
                    $vacant->ToDate = null;

                    return $vacant;
                })
                ->values();

            return $query;
        }
        // Default: current/live plantilla (no date filter)
        $query = vwplantillastructure::select([
            'vwplantillaStructure.ControlNo',
            'vwplantillaStructure.ID',
            'vwplantillaStructure.office',
            'vwplantillaStructure.office2',
            'vwplantillaStructure.group',
            'vwplantillaStructure.division',
            'vwplantillaStructure.section',
            'vwplantillaStructure.unit',
            'vwplantillaStructure.position',
            'vwplantillaStructure.PositionID',
            'vwplantillaStructure.PageNo',
            'vwplantillaStructure.ItemNo',
            'vwplantillaStructure.SG',
            'vwplantillaStructure.Funded',
            'vwplantillaStructure.level',
            'vwplantillaStructure.Name1',
            'vwplantillaStructure.Pics',
            'vwplantillaStructure.Status as plantillaStatus',
            'vwplantillaStructure.Name4',
            'vwplantillaStructure.OfficeID',
            'yDesignation.Status as designationStatus',
            'yDesignation.PMID as designationPositionId',
        ])
            ->leftJoin('yDesignation', 'vwplantillaStructure.PositionID', '=', 'yDesignation.PMID')
            ->where('vwplantillaStructure.office', $office)
            ->get()
            ->unique('ID')
            ->values();

        return $query;
    }
}
