<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller; // Make sure to import the base Controller

class StructureDetailController extends Controller
{

    // updating
    public function updateFunded(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'ID' => 'required|string',
            'Funded'     => 'required|boolean',
            'ItemNo'     => 'required|string', // Added ItemNo validation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $Id = $request->input('ID');
        $funded = $request->input('Funded');
        $itemNo = $request->input('ItemNo'); // Get ItemNo from request

        try {
            $updatedCount = DB::table('tblStructureDetails')
                ->where('ID', $Id)
                ->where('ItemNo', $itemNo) // Added ItemNo to the where clause
                ->update(['Funded' => $funded]);

            if ($updatedCount > 0) {
                return response()->json(['message' => 'Funded status updated successfully!'], 200);
            } else {
                return response()->json(['message' => 'Record not found for the given PositionID and ItemNo, or no changes were made to Funded status.'], 404);
            }
        } catch (\Exception $e) {
            // Log::error('Error updating Funded status: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating the Funded status.'], 500);
        }
    }


}
