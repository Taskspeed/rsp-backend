<?php

namespace Database\Seeders;

use App\Models\excel\Children;
use App\Models\excel\Civil_service_eligibity;
use App\Models\excel\Education_background;
use App\Models\excel\Learning_development;
use App\Models\excel\nFamily;
use App\Models\excel\nPersonal_info;
use App\Models\excel\Personal_declarations;
use App\Models\excel\references;
use App\Models\excel\skill_non_academic;
use App\Models\excel\Voluntary_work;
use App\Models\excel\Work_experience;
use App\Models\JobBatchesRsp;
use App\Models\Submission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    /**
     * Number of fake applicants to generate.
     */
    protected int $count = 1;
 
    public function run(): void
    {
        $jobBatchIds = JobBatchesRsp::pluck('id');
 
        if ($jobBatchIds->isEmpty()) {
            $this->command?->warn('No rows found in job_batches_rsp. Seed JobBatchesRsp first — skipping RspApplicationSeeder.');
            return;
        }
 
        for ($i = 0; $i < $this->count; $i++) {
            $this->createApplicant($jobBatchIds->random());
        }
 
        $this->command?->info("Seeded {$this->count} RSP applicants.");
    }
 
    protected int $jobBatchesRspId = 64;


    protected function createApplicant(int $jobBatchesRspId): void
    {
        $email = 'taskspeed2002@gmail.com';
 
        // profile photo — stored to 'temp_images' same as applicationCreate() does
        // for a freshly uploaded image, before the personal record exists
        $imagePath = 'temp_images/' . Str::uuid() . '.jpg';
        Storage::disk('public')->put($imagePath, $this->fakeImageBytes('2X2 ID PHOTO'));
 
        // ---- personal info + family fields, merged like applicationCreate() does ----
        $data = [
            'lastname'         => fake()->lastName(),
            'firstname'        => fake()->firstName(),
            'middlename'       => fake()->lastName(),
            'name_extension'   => fake()->randomElement([null, 'Jr.', 'Sr.', 'III']),
            'date_of_birth'    => fake()->dateTimeBetween('-60 years', '-21 years')->format('d/m/Y'),
            'sex'              => fake()->randomElement(['Male', 'Female']),
            'place_of_birth'   => fake()->city(),
            'weight'           => fake()->numberBetween(45, 95),
            'height'           => fake()->randomFloat(2, 1.4, 1.9),
            'blood_type'       => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'gsis_no'          => fake()->numerify('##########'),
            'pagibig_no'       => fake()->numerify('####-####-####'),
            'philhealth_no'    => fake()->numerify('##-#########-#'),
            'sss_no'           => fake()->numerify('##-#######-#'),
            'tin_no'           => fake()->numerify('###-###-###-###'),
            'image_path'       => $imagePath,
 
            'civil_status'       => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'citizenship'        => 'Filipino',
            'citizenship_status' => fake()->randomElement(['By Birth', 'By Naturalization']),
 
            'residential_house'         => fake()->buildingNumber(),
            'residential_street'        => fake()->streetName(),
            'residential_subdivision'   => fake()->optional()->word(),
            'residential_barangay'      => 'Barangay ' . fake()->numberBetween(1, 30),
            'residential_city'          => 'Tagum City',
            'residential_province'      => 'Davao del Norte',
            'residential_zip'           => '8100',
 
            'permanent_house'         => fake()->buildingNumber(),
            'permanent_street'        => fake()->streetName(),
            'permanent_subdivision'   => fake()->optional()->word(),
            'permanent_barangay'      => 'Barangay ' . fake()->numberBetween(1, 30),
            'permanent_city'          => 'Tagum City',
            'permanent_province'      => 'Davao del Norte',
            'permanent_zip'           => '8100',
 
            'telephone_number'   => fake()->optional()->numerify('(084) ###-####'),
            'cellphone_number'   => '09' . fake()->numerify('#########'),
            'email_address'      => $email,
            'agency_employee_no' => null,
            'umId'               => fake()->optional()->numerify('UM-#######'),
            'philSys'            => fake()->optional()->numerify('####-####-####-####'),
            'gender_prefer'      => null,
            'other_specify'      => null,
            'Ppurok'             => 'Purok ' . fake()->numberBetween(1, 10),
            'Rpurok'             => 'Purok ' . fake()->numberBetween(1, 10),
 
            'ethnic_group'    => fake()->optional()->randomElement(['Cebuano', 'Ata Manobo', 'Mandaya', null]),
            'ethnic_specify'  => null,
 
            // // family
            // 'spouse_name'                 => null,
            // 'spouse_firstname'            => null,
            // 'spouse_middlename'           => null,
            // 'spouse_extension'            => null,
            // 'spouse_occupation'           => null,
            // 'spouse_employer'             => null,
            // 'spouse_employer_address'     => null,
            // 'spouse_employer_telephone'   => null,
            // 'father_lastname'    => fake()->lastName(),
            // 'father_firstname'   => fake()->firstName('male'),
            // 'father_middlename'  => fake()->lastName(),
            // 'father_extension'   => null,
            // 'mother_lastname'    => fake()->lastName(),
            // 'mother_firstname'   => fake()->firstName('female'),
            // 'mother_middlename'  => fake()->lastName(),
            // 'mother_maidenname'  => fake()->lastName(),
        ];
 
        // if married, fill in spouse details
        // if ($data['civil_status'] === 'Married') {
        //     $data = array_merge($data, [
        //         'spouse_firstname'  => fake()->firstName(),
        //         'spouse_middlename' => fake()->lastName(),
        //         'spouse_name'       => fake()->lastName(),
        //         'spouse_occupation' => fake()->jobTitle(),
        //         'spouse_employer'   => fake()->company(),
        //     ]);
        // }
 
        // nPersonal_info + nFamily created from the same merged array,
        // same pattern as applicationCreate()
        $personal = nPersonal_info::create($data);
 
        // nFamily::create(array_merge($data, [
        //     'nPersonalInfo_id' => $personal->id,
        // ]));
 
        // ---- children ----
        foreach (range(1, fake()->numberBetween(0, 3)) as $n) {
            Children::create([
                'nPersonalInfo_id' => $personal->id,
                'child_name'       => fake()->firstName() . ' ' . $data['lastname'],
                'birth_date'       => fake()->dateTimeBetween('-20 years', '-1 years')->format('d/m/Y'),
            ]);
        }
 
        // ---- education background ----
        $levels = ['Elementary', 'Secondary', 'Vocational/Trade Course', 'College', 'Graduate Studies'];
        foreach ($levels as $level) {
            $fromYear = fake()->numberBetween(1995, 2015);
            Education_background::create([
                'nPersonalInfo_id' => $personal->id,
                'level'            => $level,
                'degree'           => $level === 'College' ? fake()->randomElement(['BS Computer Science', 'BS Public Administration', 'BS Accountancy']) : fake()->optional()->word(),
                'attendance_from'  => (string) $fromYear,
                'attendance_to'    => (string) ($fromYear + fake()->numberBetween(1, 4)),
                'highest_units'    => fake()->optional()->numberBetween(0, 30),
                'year_graduated'   => (string) ($fromYear + fake()->numberBetween(1, 4)),
                'scholarship'      => fake()->optional()->word(),
                'attachment_path'  => $this->storeFakeAttachment($personal->id, 'education', fake()->randomElement(['jpg', 'pdf'])),
            ]);
        }
 
        // ---- trainings ----
        foreach (range(1, fake()->numberBetween(0, 3)) as $n) {
            Learning_development::create([
                'nPersonalInfo_id'    => $personal->id,
                'training_title'      => fake()->catchPhrase(),
                'inclusive_date_from' => fake()->dateTimeBetween('-5 years', '-2 years')->format('d/m/Y'),
                'inclusive_date_to'   => fake()->dateTimeBetween('-2 years', '-1 years')->format('d/m/Y'),
                'number_of_hours'     => fake()->numberBetween(4, 40),
                'type'                => fake()->randomElement(['Managerial', 'Technical', 'Supervisory']),
                'conducted_by'        => fake()->company(),
                'attachment_path'     => $this->storeFakeAttachment($personal->id, 'training', fake()->randomElement(['jpg', 'pdf'])),
            ]);
        }
 
        // ---- work experience ----
        foreach (range(1, fake()->numberBetween(0, 3)) as $n) {
            $isCurrent = fake()->boolean(30);
            Work_experience::create([
                'nPersonalInfo_id'       => $personal->id,
                'work_date_from'         => fake()->dateTimeBetween('-10 years', '-3 years')->format('d/m/Y'),
                'work_date_to'           => $isCurrent ? 'PRESENT' : fake()->dateTimeBetween('-3 years', '-1 years')->format('d/m/Y'),
                'position_title'         => fake()->jobTitle(),
                'department'             => fake()->company(),
                'monthly_salary'         => fake()->numberBetween(18000, 60000),
                'salary_grade'           => 'SG-' . fake()->numberBetween(1, 24),
                'status_of_appointment'  => fake()->randomElement(['Permanent', 'Casual', 'Job Order', 'Contractual']),
                'government_service'     => fake()->randomElement(['Yes', 'No']),
                'attachment_path'        => $this->storeFakeAttachment($personal->id, 'experience', fake()->randomElement(['jpg', 'pdf'])),
            ]);
        }
 
        // ---- voluntary work ----
        foreach (range(1, fake()->numberBetween(0, 2)) as $n) {
            Voluntary_work::create([
                'nPersonalInfo_id'    => $personal->id,
                'organization_name'   => fake()->company(),
                'inclusive_date_from' => fake()->dateTimeBetween('-4 years', '-2 years')->format('d/m/Y'),
                'inclusive_date_to'   => fake()->dateTimeBetween('-2 years', '-1 years')->format('d/m/Y'),
                'number_of_hours'     => fake()->numberBetween(4, 100),
                'position'            => fake()->jobTitle(),
            ]);
        }
 
        // ---- civil service eligibility ----
        foreach (range(1, fake()->numberBetween(1, 2)) as $n) {
            Civil_service_eligibity::create([
                'nPersonalInfo_id'      => $personal->id,
                'eligibility'           => fake()->randomElement(['Career Service Professional', 'Career Service Sub-Professional', 'RA 1080 (Board/Bar)']),
                'rating'                => fake()->randomFloat(2, 75, 99),
                'date_of_examination'   => fake()->dateTimeBetween('-8 years', '-1 years')->format('d/m/Y'),
                'place_of_examination'  => fake()->city(),
                'license_number'        => fake()->numerify('LIC-#######'),
                'date_of_validity'      => fake()->optional()->dateTimeBetween('now', '+3 years')?->format('d/m/Y'),
                'attachment_path'       => $this->storeFakeAttachment($personal->id, 'eligibility', fake()->randomElement(['jpg', 'pdf'])),
            ]);
        }
 
        // ---- skills / non-academic ----
        foreach (range(1, fake()->numberBetween(0, 3)) as $n) {
            skill_non_academic::create([
                'nPersonalInfo_id' => $personal->id,
                'skill'            => fake()->randomElement(['MS Office', 'Public Speaking', 'Data Analysis', 'Graphic Design']),
                'non_academic'     => fake()->optional()->word(),
                'organization'     => fake()->optional()->company(),
            ]);
        }
 
        // ---- references ----
        foreach (range(1, 3) as $n) {
            references::create([
                'nPersonalInfo_id' => $personal->id,
                'full_name'        => fake()->name(),
                'address'          => fake()->address(),
                'contact_number'   => '09' . fake()->numerify('#########'),
            ]);
        }
 
        // ---- personal declarations (CS Form 212 questions 34-40) ----
        Personal_declarations::create([
            'nPersonalInfo_id' => $personal->id,
 
            'question_34a' => 'Related within the third degree to appointing/recommending authority?',
            'question_34b' => 'Related within the fourth degree to the head of the office?',
            'response_34'  => 'No',
 
            'question_35a'          => 'Ever been found guilty of any administrative offense?',
            'response_35a'          => 'No',
            'question_35b'          => 'Ever been criminally charged before any court?',
            'response_35b_date'     => null,
            'response_35b_status'   => null,
 
            'question_36' => 'Ever been convicted of any crime or violation of law?',
            'response_36' => 'No',
 
            'question_37' => 'Ever been separated from the service in any government/private entity?',
            'response_37' => 'No',
 
            'question_38a' => 'Immigrant or permanent resident of another country?',
            'response_38a' => 'No',
            'question_38b' => 'Has pending case in another country related to residency?',
            'response_38b' => 'No',
 
            'question_39' => 'Indigenous group member?',
            'response_39' => fake()->boolean(20) ? 'Yes' : 'No',
 
            'question_40a' => 'Has member of family employed in government?',
            'response_40a' => 'No',
            'question_40b' => 'Has PWD ID?',
            'response_40b' => 'No',
            'question_40c' => 'Solo parent?',
            'response_40c' => fake()->boolean(15) ? 'Yes' : 'No',
 
            'chronic'        => fake()->boolean(5) ? 1 : 0,
            'Psychosocial'   => fake()->boolean(5) ? 1 : 0,
            'Orthopedic'     => fake()->boolean(5) ? 1 : 0,
            'Communication'  => fake()->boolean(5) ? 1 : 0,
            'Learning'       => fake()->boolean(5) ? 1 : 0,
            'Mental'         => fake()->boolean(5) ? 1 : 0,
            'Visual'         => fake()->boolean(5) ? 1 : 0,
        ]);
 
        // ---- other_document / pds ----
        // Not seeded — model class unconfirmed (see docblock note #2). Once you give me
        // the real class name, swap it in below — the file-generation part is ready:
        //
        // OtherDocument::create([
        //     'nPersonalInfo_id' => $personal->id,
        //     'document'         => $this->storeFakeAttachment($personal->id, 'other_document', 'pdf'),
        // ]);
        //
        // Pds::create([
        //     'nPersonalInfo_id' => $personal->id,
        //     'pds_file'         => $this->storeFakeAttachment($personal->id, 'pds_file', 'pdf'),
        // ]);
 
        // ---- submission ----
        Submission::create([
            'nPersonalInfo_id'   => $personal->id,
            'job_batches_rsp_id' => $jobBatchesRspId,
        ]);
    }
 
    /**
     * Generate a real JPG/PDF placeholder for a given attachment field and store it
     * to the 'public' disk, mirroring the folder structure applicationCreate() uses
     * (applicant_files/{id}/{folder}/{filename}). Returns the stored relative path.
     */
    protected function storeFakeAttachment(int $personalId, string $folder, string $ext = 'jpg'): string
    {
        $filename = Str::uuid() . '.' . $ext;
        $path = "applicant_files/{$personalId}/{$folder}/{$filename}";
 
        $content = $ext === 'pdf'
            ? $this->fakePdfBytes(strtoupper(str_replace('_', ' ', $folder)) . ' ATTACHMENT')
            : $this->fakeImageBytes(strtoupper(str_replace('_', ' ', $folder)) . ' ATTACHMENT');
 
        Storage::disk('public')->put($path, $content);
 
        return $path;
    }
 
    /**
     * Build a small real JPEG in-memory (GD, no external calls) with a text label
     * so it's obvious at a glance which record/field a seeded image belongs to.
     */
    protected function fakeImageBytes(string $label): string
    {
        $width = 400;
        $height = 300;
 
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, random_int(170, 230), random_int(170, 230), random_int(170, 230));
        imagefill($image, 0, 0, $bg);
 
        $textColor = imagecolorallocate($image, 40, 40, 40);
        imagestring($image, 5, 15, ($height / 2) - 10, $label, $textColor);
        imagestring($image, 3, 15, ($height / 2) + 15, date('Y-m-d H:i:s'), $textColor);
 
        ob_start();
        imagejpeg($image, null, 80);
        $bytes = ob_get_clean();
        imagedestroy($image);
 
        return $bytes;
    }
 
    /**
     * Build a small, valid, single-page PDF in-memory (raw PDF syntax, no library
     * dependency) with a text label so it's readable when opened.
     */
    protected function fakePdfBytes(string $label): string
    {
        $label = str_replace(['(', ')', '\\'], '', $label);
        $streamContent = "BT /F1 18 Tf 50 700 Td ({$label}) Tj ET";
 
        $objects = [];
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $objects[4] = "4 0 obj\n<< /Length " . strlen($streamContent) . " >>\nstream\n{$streamContent}\nendstream\nendobj\n";
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
 
        $pdf = "%PDF-1.4\n";
        $offsets = [];
 
        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $obj;
        }
 
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
 
        foreach ($objects as $num => $obj) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$num]);
        }
 
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";
 
        return $pdf;
    }
}
