<?php

namespace App\Http\Controllers;

use App\Models\excel\nPersonal_info;
use App\Models\JobBatchesRsp;
use App\Models\Submission;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function getDataApplicantJobpostApplied(?int $jobPostId = null, ?int $nPersonalId = null)
    {

        $findData = Submission::where('job_batches_rsp_id', $jobPostId)->where('nPersonalInfo_id', $nPersonalId)->first();


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

    public function listOfNotChosen($jobPostId)
    {

    //    // throw error if there is no hire on the job post before proceed
    //     $jobHired = Submission::where('job_batches_rsp_id', $jobPostId)
    //         ->where('status', 'Hired')
    //         ->exists();

    //     if (!$jobHired) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No hired applicant found for this job post. Please hire an applicant first before sending emails to those not chosen.'
    //         ], 422);
    //     }

        $job = JobBatchesRsp::where('id', $jobPostId)
            ->select('id', 'Position', 'ItemNo', 'SalaryGrade', 'Office')
            ->with([
                'submissions' => function ($query) {
                    $query->select(
                        'id',
                        'job_batches_rsp_id',
                        'nPersonalInfo_id',
                        'ControlNo',
                        'status',
                    )
                        ->where('status', 'Qualified')
                        ->where(function ($query) {
                            $query->where('application_status', '!=', 'Withdrawn')
                                ->orWhereNull('application_status');
                        })
                        ->with([
                            'nPersonalInfo:id,firstname,lastname,residential_street,residential_barangay,residential_city,residential_province,Rpurok',
                        ]);
                }
            ])
            ->first();

        if (!$job) {
            return response()->json(['message' => 'Job post not found.'], 404);
        }

        $applicants = [];

        foreach ($job->submissions as $submission) {

            // EXTERNAL
            if ($submission->nPersonalInfo_id) {
                $applicants[] = [
                    'jobPostId'        => $job->id,
                    'submissionId'     => $submission->id,
                    'firstname'        => $submission->nPersonalInfo->firstname ?? null,
                    'lastname'         => $submission->nPersonalInfo->lastname ?? null,
                    'status'           => $submission->status,
                    'applicant_status' => 'EXTERNAL',
                    'purok'            => $submission->nPersonalInfo->Rpurok ?? null,
                    'street'           => $submission->nPersonalInfo->residential_street ?? null,
                    'barangay'         => $submission->nPersonalInfo->residential_barangay ?? null,
                    'city'             => $submission->nPersonalInfo->residential_city ?? null,
                    'province'         => $submission->nPersonalInfo->residential_province ?? null,
                ];
            }

            // INTERNAL
            elseif (!empty($submission->ControlNo)) {
                $personal = DB::table('xPersonalAddt')
                    ->join('xPersonal', 'xPersonalAddt.ControlNo', '=', 'xPersonal.ControlNo')
                    ->where('xPersonalAddt.ControlNo', $submission->ControlNo)
                    ->select(
                        'xPersonal.Firstname',
                        'xPersonal.Surname',
                        'xPersonalAddt.EmailAdd',
                        'xPersonalAddt.Rpurok',
                        'xPersonalAddt.Rstreet',
                        'xPersonalAddt.Rbarangay',
                        'xPersonalAddt.Rcity',
                        'xPersonalAddt.Rprovince',
                    )
                    ->first();

                if (!$personal) {
                    continue;
                }

                $applicants[] = [
                    'jobPostId'        => $job->id,
                    'submissionId'     => $submission->id,
                    'controlno'        => $submission->ControlNo,
                    'firstname'        => $personal->Firstname,
                    'lastname'         => $personal->Surname,
                    'status'           => $submission->status,
                    'applicant_status' => 'INTERNAL',
                    'purok'            => $personal->Rpurok,
                    'street'           => $personal->Rstreet,
                    'barangay'         => $personal->Rbarangay,
                    'city'             => $personal->Rcity,
                    'province'         => $personal->Rprovince,
                    'email'            => $personal->EmailAdd,
                ];
            }
        }

        //  Fixed: empty() — return message only when list is truly empty
        if (empty($applicants)) {
            return $this->infoMessage('There are no  applicants', 200);
        }

        return $this->successMessage($applicants, 'Successfully Fetched', 200);
    }
}
