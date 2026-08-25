<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS vwEmployee');

        DB::statement("
            CREATE VIEW vwEmployee AS
            -- Source 1: mula sa EmployeeAssign table
            SELECT
                COALESCE(ra.control_no, ea.control_no)   AS ControlNo,
                COALESCE(ra.office, ea.office)           AS office,
                COALESCE(ra.office2, ea.office2)         AS office2,
                COALESCE(ra.[group], ea.[group])         AS [group],
                COALESCE(ra.division, ea.division)       AS division,
                COALESCE(ra.section, ea.section)         AS section,
                COALESCE(ra.unit, ea.unit)               AS unit,
                COALESCE(ra.position, ea.position)       AS position,
                COALESCE(ra.name, ea.name)               AS name,
                ra.re_assign_date                        AS re_assign_date,
                ra.active                                AS active,
                act.Status                               AS status,
                act.Grades                               AS grades,
                ISNULL(ext.rank, 'Employee')              AS rank,
                ISNULL(ext.job_title, 'Employee')         AS job_title,
                ext.suffix                                AS suffix,
                ext.prefix                                AS prefix,
                CAST(NULL AS INT)                         AS itemNo,
                CAST(NULL AS INT)                         AS pageNo,
                CAST(NULL AS INT)                         AS tblStructureID,
                CAST(NULL AS INT)                         AS positionID,
                CASE 
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C1','D1') THEN '10'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C2','D2') THEN '11'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C3','D3') THEN '12'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C4','D4') THEN '13'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C5','D5') THEN '14'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C6','D6') THEN '15'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C7','D7') THEN '16'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C8','D8') THEN '17'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C9','D9') THEN '18'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E1' THEN '21'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E2' THEN '22'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E3' THEN '23'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E4' THEN '24'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E5' THEN '25'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E6' THEN '26'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E7' THEN '27'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E8' THEN '28'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) = 'E9' THEN '29'
                    ELSE NULL
                END AS sg,
                CASE 
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C1','D1') THEN '1'
                    WHEN act.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act.Grades))) IN ('C2','C3','C4','C5','C6','C7','C8','C9','D2','D3','D4','D5','D6','D7','D8','D9','E1','E2','E3','E4','E5','E6','E7','E8','E9') THEN '2'
                    ELSE NULL
                END AS level
                -- NOTE: walang StructureID/ID linking column sa Source 1 (employee_assigns),
                -- kaya hindi ma-join sa vwplantillalevel dito. Manatiling NULL ang sg/level
                -- para sa non-CASUAL na galing sa source na ito.
            FROM employee_assigns ea
            LEFT JOIN employee_reassigns ra 
                ON ra.control_no = ea.control_no AND ra.active = 1
            LEFT JOIN vwActive act 
                ON act.ControlNo = ea.control_no
            LEFT JOIN employee_extra_details ext
                ON ext.control_no = ea.control_no

            UNION ALL

            -- Source 2: mula sa vwplantillastructure
            SELECT
                COALESCE(ra2.control_no, ps.ControlNo)   AS ControlNo,
                COALESCE(ra2.office, ps.Office)          AS office,
                COALESCE(ra2.office2, ps.office2)        AS office2,
                COALESCE(ra2.[group], ps.[group])        AS [group],
                COALESCE(ra2.division, ps.division)      AS division,
                COALESCE(ra2.section, ps.section)        AS section,
                COALESCE(ra2.unit, ps.unit)              AS unit,
                COALESCE(ra2.position, ps.position)      AS position,
                COALESCE(ra2.name, ps.name4)             AS name,
                ra2.re_assign_date                       AS re_assign_date,
                ra2.active                               AS active,
                act2.Status                              AS status,
                act2.Grades                              AS grades,
                ISNULL(ext2.rank, 'Employee')             AS rank,
                ISNULL(ext2.job_title, 'Employee')        AS job_title,
                ext2.suffix                               AS suffix,
                ext2.prefix                               AS prefix,
                ps.ItemNo                                AS itemNo,
                ps.PageNo                                AS pageNo,
                ps.ID                                    AS tblStructureID,
                ps.PositionID                            AS positionID,
                COALESCE(
                    CASE 
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C1','D1') THEN '10'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C2','D2') THEN '11'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C3','D3') THEN '12'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C4','D4') THEN '13'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C5','D5') THEN '14'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C6','D6') THEN '15'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C7','D7') THEN '16'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C8','D8') THEN '17'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C9','D9') THEN '18'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E1' THEN '21'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E2' THEN '22'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E3' THEN '23'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E4' THEN '24'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E5' THEN '25'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E6' THEN '26'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E7' THEN '27'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E8' THEN '28'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) = 'E9' THEN '29'
                        ELSE NULL
                    END,
                    pl.SG
                ) AS SG,
                COALESCE(
                    CASE 
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C1','D1') THEN '1'
                        WHEN act2.Status = 'CASUAL' AND UPPER(LTRIM(RTRIM(act2.Grades))) IN ('C2','C3','C4','C5','C6','C7','C8','C9','D2','D3','D4','D5','D6','D7','D8','D9','E1','E2','E3','E4','E5','E6','E7','E8','E9') THEN '2'
                        ELSE NULL
                    END,
                    pl.Level
                ) AS SGLevel
            FROM vwplantillastructure ps
            LEFT JOIN employee_reassigns ra2 
                ON ra2.control_no = ps.ControlNo AND ra2.active = 1
            LEFT JOIN vwActive act2 
                ON act2.ControlNo = ps.ControlNo
            LEFT JOIN employee_extra_details ext2
                ON ext2.control_no = ps.ControlNo
            LEFT JOIN vwplantillalevel pl
                ON pl.ID = ps.ID
            WHERE ps.ControlNo IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vwEmployee');
    }
};