<?php

namespace App\Http\Controllers;

use App\Models\excel\nPersonal_info;
use App\Models\Submission;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{


    use ApiResponseTrait;

    // fetch  the list of application base on the email be used
    public function getListOfApplicant(string $email)
    {
        $listOfApplication = nPersonal_info::with(['job_batches_rsp:id,Office,Position,SalaryGrade,post_date,end_date'])
            ->select('id', 'firstname', 'lastname', 'date_of_birth', 'email_address')
            ->where('email_address', $email)
            ->get()
            ->map(function ($applicant) {
                $batch = $applicant->job_batches_rsp->first(); // grab the first (or only) job batch

                return [
                    'personal_id' => $applicant->id,
                    'firstname' => $applicant->firstname,
                    'lastname' => $applicant->lastname,
                    'date_of_birth' => $applicant->date_of_birth
                        ? Carbon::createFromFormat('d/m/Y', $applicant->date_of_birth)->format('F j, Y')
                        : null,
                    'email_address' => $applicant->email_address,
                    'office' => $batch?->Office,
                    'applied_position' => $batch?->Position,
                    'salary_grade' => $batch?->SalaryGrade,
                    'post_date' => $batch?->post_date ? Carbon::parse($batch->post_date)->format('F j, Y') : null,
                    'end_date'  => $batch?->end_date ? Carbon::parse($batch->end_date)->format('F j, Y') : null,
                    'application_applied_date' => $batch?->pivot?->created_at,
                    'application_status' => $batch?->pivot?->status,
                ];
            });

        if ($listOfApplication->isEmpty()) {
            return $this->successMessage($listOfApplication, 'there is no application found', 200);
        }
        return $this->successMessage($listOfApplication, 'success', 200);
    }


    // get the pds of the applicant 
    public function getApplicantPdsExternalApplication(string $email)
    {
        $personalId = nPersonal_info::with([
            'family',
            'children',
            'education',
            'work_experience',
            'training',
            'eligibity',
            'personal_declarations',
            'skills',
            'references'
        ])
            ->where('email_address', $email)
            ->latest() // orders by created_at desc, then first() grabs the newest
            ->first();

        if (!$personalId) {
            return $this->errorMessage('applicant not found', 404);
        }

        // convert attachment_path (relative storage path) → full accessible URL
        // sa lahat ng relationships na may attachment_path column
        $attachmentRelations = ['education', 'work_experience', 'training', 'eligibity'];

        foreach ($attachmentRelations as $relation) {
            $personalId->{$relation}->each(function ($item) {
                if (!empty($item->attachment_path)) {
                    $item->attachment_path = Storage::disk('public')->url($item->attachment_path);
                }
            });
        }

        $categories = ['other_document', 'pds_file'];
        $file = [];

        foreach ($categories as $category) {
            $folder = "applicant_files/{$personalId->getKey()}/{$category}";

            if (Storage::disk('public')->exists($folder)) {
                $file[$category] = collect(Storage::disk('public')->files($folder))
                    ->map(fn($path) => Storage::disk('public')->url($path))
                    ->values();
            } else {
                $file[$category] = [];
            }
        }

        $personalId->setAttribute('file', $file);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $personalId,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getDataApplicantJobpostApplied(?int $jobPostId = null, ?int $nPersonalId = null){

        $findData = Submission::where('job_batches_rsp_id',$jobPostId)->where('nPersonalInfo_id',$nPersonalId)->first();


         if (!$findData) {
            return $this->errorMessage('applicant not found', 404);
        }

       $personalId = nPersonal_info::with([
            'family',
            'children',
            'education',
            'work_experience',
            'training',
            'eligibity',
            'personal_declarations',
            'skills',
            'references'
        ])
            ->find($findData->nPersonalInfo_id);

      if (!$personalId) {
            return $this->errorMessage('applicant data not found', 404);
        }

        // convert attachment_path (relative storage path) → full accessible URL
        // sa lahat ng relationships na may attachment_path column
        $attachmentRelations = ['education', 'work_experience', 'training', 'eligibity'];

        foreach ($attachmentRelations as $relation) {
            $personalId->{$relation}->each(function ($item) {
                if (!empty($item->attachment_path)) {
                    $item->attachment_path = Storage::disk('public')->url($item->attachment_path);
                }
            });
        }

        $categories = ['other_document', 'pds_file'];
        $file = [];

        foreach ($categories as $category) {
            $folder = "applicant_files/{$personalId->getKey()}/{$category}";

            if (Storage::disk('public')->exists($folder)) {
                $file[$category] = collect(Storage::disk('public')->files($folder))
                    ->map(fn($path) => Storage::disk('public')->url($path))
                    ->values();
            } else {
                $file[$category] = [];
            }
        }

        $personalId->setAttribute('file', $file);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $personalId,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}