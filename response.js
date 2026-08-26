{
    "WES": [
        {
            "ID": "5",
            "controlno": "011790",
            "DurationFrom": "03\/21\/2015",
            "DurationTo": "09\/30\/2016",
            "Position": "ADMINISTRATIVE AIDE I",
            "officeName": "City Mayor's Office - Administrative(IT)",
            "Supervisor": "Mae Elizabeth Tomado\/Computer Programmer II",
            "Organization": "Local Government Unit of Tagum\/JV Ayala, Apokon, Tagum City",
            "status": "JOB ORDER",
            "Accomplishments": [],
            "Duties": [
                {
                    "ID": "12",
                    "WESID": "5",
                    "Duties": "Under supervision, writes computer programs and performs research and analysis work as assigned"
                },
                {
                    "ID": "13",
                    "WESID": "5",
                    "Duties": "Responsible for the proper execution of programs assigned to him for development and sees to it that all developed programs adheres to the specifications"
                },
                {
                    "ID": "14",
                    "WESID": "5",
                    "Duties": "Responsible for the proper execution of programs assigned for development and sees to it that all developed programs adheres to the specifications"
                },
                {
                    "ID": "15",
                    "WESID": "5",
                    "Duties": "Responsible for managing databases and sees to it that it has back up everyday"
                }
            ]
        },
        {
            "ID": "2",
            "controlno": "011790",
            "DurationFrom": "11\/01\/2016",
            "DurationTo": "05\/28\/2019",
            "Position": "COMPUTER PROGRAMMER I",
            "officeName": "City Mayor's Office - Administrative(IT)",
            "Supervisor": "Mae Elizabeth Tomado\/Computer Programmer II",
            "Organization": "JV Ayala, Apokon, Tagum City",
            "status": "JOB ORDER",
            "Accomplishments": [],
            "Duties": [
                {
                    "ID": "8",
                    "WESID": "2",
                    "Duties": "Under supervision, writes computer programs and performs research and analysis work as assigned"
                },
                {
                    "ID": "9",
                    "WESID": "2",
                    "Duties": "Responsible for the proper execution of programs assigned to him for development and sees to it that all developed programs adheres to the specifications"
                },
                {
                    "ID": "10",
                    "WESID": "2",
                    "Duties": "Responsible for the proper execution of programs assigned for development and sees to it that all developed programs adheres to the specifications"
                },
                {
                    "ID": "11",
                    "WESID": "2",
                    "Duties": "Responsible for managing databases and sees to it that it has back up everyday"
                }
            ]
        },
        {
            "ID": "3",
            "controlno": "011790",
            "DurationFrom": "05\/01\/2019",
            "DurationTo": "Present",
            "Position": "COMPUTER PROGRAMMER II",
            "officeName": "Office of the City Mayor\/Research and Management Information System Division",
            "Supervisor": "Joseph Nelson Briones\/Executive Assistant III",
            "Organization": "Local Government Unit of Tagum",
            "status": null,
            "Accomplishments": [],
            "Duties": [
                {
                    "ID": "55",
                    "WESID": "3",
                    "Duties": "Analyzes, evaluates, and recommends procedures to define data processing needs, inadequate guidelines, significant deviations, and other anticipated problems"
                },
                {
                    "ID": "56",
                    "WESID": "3",
                    "Duties": "Confers with users to gain understanding of needed changes or modifications to existing programs and resolve questions of program intent, data input, and output requirements"
                },
                {
                    "ID": "57",
                    "WESID": "3",
                    "Duties": "Conducts internal checks and controls"
                },
                {
                    "ID": "58",
                    "WESID": "3",
                    "Duties": "Designs and develops information systems in accordance with approved system analysis"
                },
                {
                    "ID": "59",
                    "WESID": "3",
                    "Duties": "Installs, replicates, maintains, and monitors application systems"
                },
                {
                    "ID": "60",
                    "WESID": "3",
                    "Duties": "Conducts the user\u2019s training"
                },
                {
                    "ID": "61",
                    "WESID": "3",
                    "Duties": "Submits Individual Performance Commitment and Review (IPCR) with Monthly Performance Output Report (MPOR) and IPCR Target every semester"
                }
            ]
        }
    ]
}

private function getExperienceData($controlNo)
{
    // =========================
    // xExperience
    // =========================
    $experience = DB::table('xExperience')
        ->select([
            'ID as id',
            'CONTROLNO',
            'WFrom',
            'WTo',
            'WPosition',
            'WCompany',
            'WSalary',
            'WGrade',
            'Status',
            'WGov',
        ])
        ->where('ControlNo', $controlNo)
        ->get()
        ->map(fn($row) => [
            'id'        => $row->id,
            'WFrom'     => $this->safeDate($row->WFrom),
            'WTo'       => $this->safeDate($row->WTo),
            'WPosition' => $this->upper($row->WPosition),
            'WCompany'  => $this->upper($row->WCompany),
            'WSalary'   => $row->WSalary
                ? '₱ ' . number_format($row->WSalary, 2)
                : '₱ 0.00',
            'WGrade'    => $row->WGrade,
            'Status'    => $this->upper($row->Status),
            'WGov'      => $this->upper($row->WGov),
            'source'    => 'xExperience',
        ]);

    // =========================
    // xService
    // =========================
    $serviceRecords = DB::table('xService')
        ->select([
            'PMID as id',
            'ControlNo',
            'FromDate',
            'ToDate',
            'Designation',
            'Office',
            'Branch',
            'RateDay',
            'RateMon',
            'Grades',
            'Steps',
            'Status',
        ])
        ->where('ControlNo', $controlNo)
        ->orderBy('FromDate')
        ->get();

    // =========================
    // Get latest service record
    // based on ToDate then FromDate
    // =========================
    $latestService = $serviceRecords
        ->sortByDesc(function ($row) {
            return [
                $row->ToDate,
                $row->FromDate,
            ];
        })
        ->first();

    // =========================
    // Map xService
    // =========================
    $service = $serviceRecords->map(function ($row) use ($latestService) {

        $fromDate = $row->FromDate
            ? \Carbon\Carbon::parse($row->FromDate)
            : null;

        $toDate = $row->ToDate
            ? \Carbon\Carbon::parse($row->ToDate)
            : null;

        $isLatest = $latestService
            && $row->id === $latestService->id;

        return [
            'id'        => $row->id,

            'WFrom'     => $fromDate
                ? $fromDate->format('d/m/Y')
                : null,

            'WTo'       => (
                $isLatest &&
                $toDate &&
                $toDate->isFuture()
            )
                ? 'PRESENT'
                : ($toDate
                    ? $toDate->format('d/m/Y')
                    : null),

            'WPosition' => $this->upper($row->Designation),

            'WCompany'  => $this->upper(
                trim(($row->Office ?? '') .
                ($row->Branch ? '/' . $row->Branch : ''))
            ),

            'WSalary'   => $row->Status === 'CONTRACTUAL'
                ? '₱ ' . number_format(($row->RateDay ?? 0) * 22, 2)
                : '₱ ' . number_format(($row->RateMon ?? 0), 2),

            'WGrade'    => trim(
                ($row->Grades ?? '') .
                ($row->Steps ? '-' . $row->Steps : '')
            ),

            'Status'    => $this->upper($row->Status),

            'WGov'      => in_array(
                strtoupper($row->Status),
                ['CONTRACTUAL', 'HONORARIUM']
            )
                ? 'NO'
                : 'YES',

            'source'    => 'xService',
        ];
    });

    // =========================
    // Merge both
    // =========================
    return $experience
        ->merge($service)
        ->sortByDesc(function ($item) {
            return \Carbon\Carbon::createFromFormat(
                'd/m/Y',
                $item['WFrom']
            )->timestamp ?? 0;
        })
        ->values()
        ->toArray();
}








































    {
                    "controlno": "011789",
                    "firstname": "NEIL BENJAMIN",
                    "lastname": "ROBLE",
                    "current_designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                    "office": "OFFICE OF THE CITY INFORMATION AND COMMUNICATIONS TECHNOLOGY MANAGEMENT OFFICER",
                    "status": "Qualified",
                    "applicant_status": "INTERNAL",
                    "education": [
                        {
                            "Education": "COLLEGE",
                            "School": "UNIVERSITY OF MINDANAO TAGUM COLLEGE",
                            "Codes": "00002",
                            "Degree": "BACHELOR OF SCIENCE IN COMPUTER SCIENCE",
                            "NumUnits": "0.0",
                            "YearLevel": "",
                            "DateAttend": "2008 - 2014",
                            "Honors": "",
                            "Graduated": "YES",
                            "Orders": "4",
                            "ControlNo": "011789",
                            "PMID": "21295",
                            "submission_id": null
                        }
                    ],
                    "experience": [
                        {
                            "id": "2744887",
                            "ControlNo": "011789",
                            "WFrom": "2021-01-01 00:00:00",
                            "WTo": "2021-02-28 00:00:00",
                            "Designation": "DATA CONTROLLER II (JOB ORDER)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "579.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744888",
                            "ControlNo": "011789",
                            "WFrom": "2020-07-01 00:00:00",
                            "WTo": "2020-12-31 00:00:00",
                            "Designation": "DATA CONTROLLER II (JOB ORDER)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "579.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744889",
                            "ControlNo": "011789",
                            "WFrom": "2020-01-01 00:00:00",
                            "WTo": "2020-06-30 00:00:00",
                            "Designation": "DATA CONTROLLER II (JOB ORDER)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "579.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744890",
                            "ControlNo": "011789",
                            "WFrom": "2019-07-01 00:00:00",
                            "WTo": "2019-12-31 00:00:00",
                            "Designation": "COMPUTER PROGRAMMER (JOB ORDER)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "432.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744891",
                            "ControlNo": "011789",
                            "WFrom": "2019-01-01 00:00:00",
                            "WTo": "2019-06-30 00:00:00",
                            "Designation": "COMPUTER PROGRAMMER (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "432.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744892",
                            "ControlNo": "011789",
                            "WFrom": "2018-07-01 00:00:00",
                            "WTo": "2018-12-31 00:00:00",
                            "Designation": "COMPUTER PROGRAMMER (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "432.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744893",
                            "ControlNo": "011789",
                            "WFrom": "2018-01-01 00:00:00",
                            "WTo": "2018-06-30 00:00:00",
                            "Designation": "COMPUTER PROGRAMMER (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "432.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744894",
                            "ControlNo": "011789",
                            "WFrom": "2017-07-01 00:00:00",
                            "WTo": "2017-12-31 00:00:00",
                            "Designation": "COMPUTER PROGRAMMER (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "432.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744895",
                            "ControlNo": "011789",
                            "WFrom": "2017-01-01 00:00:00",
                            "WTo": "2017-06-30 00:00:00",
                            "Designation": "COMPUTER PROGRAMMER (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "432.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744896",
                            "ControlNo": "011789",
                            "WFrom": "2016-08-01 00:00:00",
                            "WTo": "2016-12-31 00:00:00",
                            "Designation": "COMPUTER PROGRAMMER (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "422.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T2",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744897",
                            "ControlNo": "011789",
                            "WFrom": "2016-07-01 00:00:00",
                            "WTo": "2016-07-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "338.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T1",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744898",
                            "ControlNo": "011789",
                            "WFrom": "2016-01-01 00:00:00",
                            "WTo": "2016-06-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "338.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T1",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744899",
                            "ControlNo": "011789",
                            "WFrom": "2015-07-01 00:00:00",
                            "WTo": "2015-12-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "338.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T1",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744900",
                            "ControlNo": "011789",
                            "WFrom": "2015-04-01 00:00:00",
                            "WTo": "2015-06-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "338.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T1",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744901",
                            "ControlNo": "011789",
                            "WFrom": "2015-01-21 00:00:00",
                            "WTo": "2015-03-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE (JOB ORDER)",
                            "Office": "CITY MAYOR`S OFFICE - ADMINISTRATIVE (IT)",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CONTRACTUAL",
                            "RateDay": "338.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "T1",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2744904",
                            "ControlNo": "011789",
                            "WFrom": "2021-03-01 00:00:00",
                            "WTo": "2021-12-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "616.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2997730",
                            "ControlNo": "011789",
                            "WFrom": "2022-07-01 00:00:00",
                            "WTo": "2022-09-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "642.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3061628",
                            "ControlNo": "011789",
                            "WFrom": "2022-10-01 00:00:00",
                            "WTo": "2022-12-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "642.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3259638",
                            "ControlNo": "011789",
                            "WFrom": "2023-07-01 00:00:00",
                            "WTo": "2023-12-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "667.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3206585",
                            "ControlNo": "011789",
                            "WFrom": "2023-01-01 00:00:00",
                            "WTo": "2023-06-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "667.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3303212",
                            "ControlNo": "011789",
                            "WFrom": "2024-07-01 00:00:00",
                            "WTo": "2024-08-01 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "667.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3370627",
                            "ControlNo": "011789",
                            "WFrom": "2025-07-01 00:00:00",
                            "WTo": "2025-08-01 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY INFORMATION AND COMMUNICATIONS TECHNOLOGY MANAGEMENT OFFICER",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "693.0",
                            "RateMon": "15246.0",
                            "RateYear": "182952.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "2887790",
                            "ControlNo": "011789",
                            "WFrom": "2022-01-01 00:00:00",
                            "WTo": "2022-06-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "642.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3284420",
                            "ControlNo": "011789",
                            "WFrom": "2024-01-01 00:00:00",
                            "WTo": "2024-06-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "667.0",
                            "RateMon": "0.0",
                            "RateYear": "0.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3327734",
                            "ControlNo": "011789",
                            "WFrom": "2025-01-01 00:00:00",
                            "WTo": "2025-06-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY INFORMATION AND COMMUNICATIONS TECHNOLOGY MANAGEMENT OFFICER",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "693.0",
                            "RateMon": "15246.0",
                            "RateYear": "182952.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3321260",
                            "ControlNo": "011789",
                            "WFrom": "2024-08-02 00:00:00",
                            "WTo": "2024-12-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY MAYOR",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "RESEARCH AND MANAGEMENT INFORMATION SYSTEM DIVISION",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "693.0",
                            "RateMon": "15246.0",
                            "RateYear": "182952.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3384060",
                            "ControlNo": "011789",
                            "WFrom": "2025-08-02 00:00:00",
                            "WTo": "2025-12-31 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY INFORMATION AND COMMUNICATIONS TECHNOLOGY MANAGEMENT OFFICER",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "720.0",
                            "RateMon": "15840.0",
                            "RateYear": "190080.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        },
                        {
                            "id": "3387697",
                            "ControlNo": "011789",
                            "WFrom": "2026-01-01 00:00:00",
                            "WTo": "2026-06-30 00:00:00",
                            "Designation": "ADMINISTRATIVE AIDE III (CLERK I) (CASUAL)",
                            "Office": "OFFICE OF THE CITY INFORMATION AND COMMUNICATIONS TECHNOLOGY MANAGEMENT OFFICER",
                            "Branch": "LGU-TAGUM",
                            "Divisions": "NONE",
                            "Sections": "NONE",
                            "Status": "CASUAL",
                            "RateDay": "720.0",
                            "RateMon": "15840.0",
                            "RateYear": "190080.0",
                            "Grades": "C3",
                            "Steps": "1",
                            "experience_status": "SERVICE"
                        }
                    ],
                    "training": [
                        {
                            "ControlNo": "011789",
                            "Training": "MINDANAO CONFERENCE FOR IT STUDENTS",
                            "Dates": "1/30/2014 - 1/30/2014",
                            "NumHours": "8.0",
                            "Conductor": "PHILIPINE SOCIETY OF INFORMATION TECHNOLOGY EDUCATORS (PSITE)",
                            "PMID": "17101",
                            "DateFrom": "01/30/2014",
                            "DateTo": "01/30/2014",
                            "type": null,
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "GRAPHIC DESIGN SEMINAR \"LOGO DESIGN\"",
                            "Dates": "3/10/2013",
                            "NumHours": "5.0",
                            "Conductor": "TAGUM GRAPHIC DESIGNERS COMMUNITY",
                            "PMID": "17102",
                            "DateFrom": "03/10/2013",
                            "DateTo": "03/10/2013",
                            "type": null,
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "TRAINING-WORKSHOP FOR MOBILE FIRST WEB DEVELOPMENT",
                            "Dates": "2023-03-28",
                            "NumHours": "8.0",
                            "Conductor": "davao del norte state college - institute of computing",
                            "PMID": "157881",
                            "DateFrom": "2023-03-28",
                            "DateTo": "2023-03-28",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "PROFESSIONAL DEVELOPMENT FOR THE CITY GOVERNMENT OF TAGUM: CAPABILITY ENHANCEMENT - TRAINING 1: ASP.NET WEB DEVELOPMENT",
                            "Dates": "2020-12-14",
                            "NumHours": "24.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157882",
                            "DateFrom": "2020-12-14",
                            "DateTo": "2020-12-16",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "CYBER SECURITY AWARENESS",
                            "Dates": "2023-10-24",
                            "NumHours": "4.0",
                            "Conductor": "DEPARTMENT OF INFORMATION AND COMMUNICATIONS TECHNOLOGY DAVAO DEL NORTE",
                            "PMID": "157883",
                            "DateFrom": "2023-10-24",
                            "DateTo": "2023-10-24",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "MOBILE DEVELOPMENT TRAINING SERIES (TRAINING 1: INTRODUCTION TO MOBILE APP DEVELOPMENT USING FLUTTER)",
                            "Dates": "2024-04-30",
                            "NumHours": "8.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157884",
                            "DateFrom": "2024-04-30",
                            "DateTo": "2024-04-30",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "MOBILE DEVELOPMENT TRAINING SERIES (TRAINING 3: SEAMLESS DATA INTEGRATION: LEVERAGING FLUTTER FOR API CONNECTIVITY)",
                            "Dates": "2024-05-03",
                            "NumHours": "8.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157885",
                            "DateFrom": "2024-05-03",
                            "DateTo": "2024-05-03",
                            "type": "technical",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "MOBILE DEVELOPMENT TRAINING SERIES (TRAINING 2: BUILDING DYNAMIC UIS: HARNESSING THE POWER OF STATEFULL WIDGETS IN FLUTTER)",
                            "Dates": "2024-05-02",
                            "NumHours": "8.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157887",
                            "DateFrom": "2024-05-02",
                            "DateTo": "2024-05-02",
                            "type": "technical",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "FULL-STACK WEB AND MOBILE APPLICATION DEVELOPMENT- TRAINING 1 : IONIC AND VUEJS",
                            "Dates": "2022-03-24",
                            "NumHours": "16.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157888",
                            "DateFrom": "2022-03-24",
                            "DateTo": "2022-03-25",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "FULL-STACK WEB AND MOBILE APPLICATION DEVELOPMENT- TRAINING 2 : FIGMA AND NEW IONIC",
                            "Dates": "2022-12-01",
                            "NumHours": "16.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157889",
                            "DateFrom": "2022-12-01",
                            "DateTo": "2022-12-02",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "PROFESSIONAL DEVELOPMENT FOR THE CITY GOVERNMENT OF TAGUM: CAPABILITY ENHANCEMENT - TRAINING 2: VUEJS SINGE PAGE APPLICATION DEVELOPMENT",
                            "Dates": "2020-12-17",
                            "NumHours": "16.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157890",
                            "DateFrom": "2020-12-17",
                            "DateTo": "2020-12-18",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "PROFESSIONAL DEVELOPMENT FOR THE CITY GOVERNMENT OF TAGUM: CAPABILITY ENHANCEMENT - TRAINING 3: DATABASE MANANGEMENT SYSTEM",
                            "Dates": "2020-12-21",
                            "NumHours": "24.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157891",
                            "DateFrom": "2020-12-21",
                            "DateTo": "2020-12-23",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "FULL-STACK WEB AND MOBILE APPLICATION DEVELOPMENT- TRAINING 4 : CRUD",
                            "Dates": "2022-12-13",
                            "NumHours": "32.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157893",
                            "DateFrom": "2022-12-13",
                            "DateTo": "2022-12-16",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "FULL-STACK WEB AND MOBILE APPLICATION DEVELOPMENT- TRAINING 3 : PHP AND MYSQL",
                            "Dates": "2022-12-05",
                            "NumHours": "16.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157894",
                            "DateFrom": "2022-12-05",
                            "DateTo": "2022-12-06",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "TRAINING-WORKSHOP FOR DESIGNING COMPREHENSIVE REPORTS",
                            "Dates": "2023-03-30",
                            "NumHours": "8.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF COMPUTING",
                            "PMID": "157895",
                            "DateFrom": "2023-03-30",
                            "DateTo": "2023-03-30",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "TRAINING-WORKSHOP FOR DESIGNING INTERACTIVE MAPS USING VUE LEAFLETS",
                            "Dates": "0023-03-29",
                            "NumHours": "8.0",
                            "Conductor": "DAVAO DEL NORTE STATE COLLEGE - INSTITUTE OF TECHNOLOGY",
                            "PMID": "157896",
                            "DateFrom": "2023-03-29",
                            "DateTo": "2023-03-29",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "PYTHON PROGRAMMING ESSENTIALS COURS TRAINING",
                            "Dates": "2021-06-30",
                            "NumHours": "40.0",
                            "Conductor": "DEPARTMENT OF INFORMATION COMMUNICATIONS TECHNOLOGY",
                            "PMID": "164613",
                            "DateFrom": "2021-06-30",
                            "DateTo": "2021-07-09",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "ONLINE INFORMATION SESSION ON SOFTWARE DEVELOPMENT AND DESIGN THINKING",
                            "Dates": "2025-03-28",
                            "NumHours": "4.0",
                            "Conductor": "DEPARTMENT OF INFORMATION AND COMMUNICATION TECHNOLOGY REGION-  11",
                            "PMID": "203995",
                            "DateFrom": "2025-03-28",
                            "DateTo": "2025-03-28",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "BLOCK CHAIN AND WEB3 DEPLOYMENT - ICT CON 2024",
                            "Dates": "2024-10-15",
                            "NumHours": "4.0",
                            "Conductor": "DEVCON DAVAO / LGU TAGUM CITY",
                            "PMID": "204392",
                            "DateFrom": "2024-10-15",
                            "DateTo": "2024-10-15",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "THE FUTURE OF ICT - EMERGING TECHNOLOGIES AND PREDICTIONS",
                            "Dates": "2024-10-15",
                            "NumHours": "4.0",
                            "Conductor": "DEVCON DAVAO/DICT/LGU TAGUM CITY",
                            "PMID": "204394",
                            "DateFrom": "2024-10-15",
                            "DateTo": "2024-10-15",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "THE FUTURE OF SOCIAL MEDIA BUSINESS AND MARKETING ",
                            "Dates": "2024-10-15",
                            "NumHours": "4.0",
                            "Conductor": "DEVCON DAVAO / DICT / LGU TAGUM CITY",
                            "PMID": "204396",
                            "DateFrom": "2024-10-15",
                            "DateTo": "2024-10-15",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "DIGITAL GOVERNANCE- BALANCING INNOVATION AND SECURITY",
                            "Dates": "2024-10-16",
                            "NumHours": "4.0",
                            "Conductor": "DICT / LGU TAGUM / DEVCON",
                            "PMID": "205845",
                            "DateFrom": "2024-10-16",
                            "DateTo": "2024-10-16",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "THE FUTURE OF FINANCIAL TECHNOLOGY",
                            "Dates": "2024-10-16",
                            "NumHours": "4.0",
                            "Conductor": "DICT / LGU TAGUM / DEVCON DAVAO",
                            "PMID": "205847",
                            "DateFrom": "2024-10-16",
                            "DateTo": "2024-10-16",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "ADVANCED GIT TECHNIQUES AND BEST PRACTICES IN VERSION CONTROL",
                            "Dates": "2024-10-17",
                            "NumHours": "4.0",
                            "Conductor": "DICT / LGU TAGUM / DEVCON DAVAO",
                            "PMID": "205848",
                            "DateFrom": "2024-10-17",
                            "DateTo": "2024-10-17",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "THE FUTURE OF WEB DEVELOPMENT: EMERGING TRENDS AND TECHNOLOGIES",
                            "Dates": "2024-10-18",
                            "NumHours": "4.0",
                            "Conductor": "DICT / LGU TAGUM / DEVCON DAVAO",
                            "PMID": "205849",
                            "DateFrom": "2024-10-18",
                            "DateTo": "2024-10-18",
                            "type": "TECHNICAL",
                            "submission_id": null
                        },
                        {
                            "ControlNo": "011789",
                            "Training": "BUILDING WITH THE LATEST FRONT-END TOOLS AND CREATING ROBUST BACK-END SYSTEMS",
                            "Dates": "2024-10-18",
                            "NumHours": "4.0",
                            "Conductor": "DICT / LGU TAGUM / DEVCON DAVAO",
                            "PMID": "205850",
                            "DateFrom": "2024-10-18",
                            "DateTo": "2024-10-18",
                            "type": "TECHNICAL",
                            "submission_id": null
                        }
                    ],
                    "eligibility": [
                        {
                            "ControlNo": "011789",
                            "Codes": "00001",
                            "CivilServe": "CIVIL SERVICE PROFESSIONAL",
                            "Dates": "2022-03-13",
                            "Rates": "80.150000000000006",
                            "Place": "tagum city",
                            "PMID": "69169",
                            "LNumber": "",
                            "LDate": "1900-01-01",
                            "submission_id": null
                        }
                    ],
                    "education_remark": null,
                    "experience_remark": null,
                    "training_remark": null,
                    "eligibility_remark": null,
                    "education_text": "• BACHELOR OF SCIENCE IN COMPUTER SCIENCE (0.0 units)",
                    "experience_text": "0 days of relevant experience",
                    "training_text": "285 hours of relevant training",
                    "eligibility_text": "• CIVIL SERVICE PROFESSIONAL - Rating: 80.150000000000006"
                },


                [2026-07-24 02:38:14] local.INFO: topApplicant: ranking result for job post {"job_batches_rsp_id":49,"position":"COMPUTER PROGRAMMER II","applicants_count":7,"ranked_result":[{"rank":1,"name":"NEIL BENJAMIN ROBLE","ControlNo":"011789","grand_total":"90.24"},{"rank":2,"name":"CLARICE JILL BRIONES","ControlNo":"012607","grand_total":"88.63"},{"rank":3,"name":"ALDWIN RAY BALOYO","ControlNo":"021460","grand_total":"83.75"},{"rank":4,"name":"ADORELY BIBAR","ControlNo":null,"grand_total":"82.75"},{"rank":5,"name":"RICHIE JOHN BASAÑEZ","ControlNo":null,"grand_total":"79.88"},{"rank":6,"name":"JHOMEL OBLAAN","ControlNo":null,"grand_total":"64.63"},{"rank":7,"name":"DOMINIC SYMON LEE","ControlNo":null,"grand_total":"61.88"}]} 
