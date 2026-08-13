<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('sqlsrv')->statement("
            CREATE VIEW dbo.vwActiveAsOf
            AS
            SELECT        
                p.ControlNo, 
                p.ControlNo AS PMISNO, 
                p.Surname, 
                p.Firstname, 
                p.Sex, 
                s.Office, 
                s.Status, 
                p.MIddlename, 
                p.BirthDate, 
                p.Pics, 
                s.Grades, 
                s.Steps, 
                s.Designation,
                p.Surname + ', ' + p.Firstname + ' ' + p.MIddlename AS Name1, 
                p.Firstname + ' ' + p.MIddlename + ' ' + p.Surname AS Name2, 
                p.Surname + ', ' + p.Firstname + 
                    CASE WHEN p.MIddlename = '' OR p.MIddlename IS NULL THEN '' 
                         ELSE ' ' + SUBSTRING(p.MIddlename, 1, 1) + '.' END AS Name3, 
                p.Firstname + 
                    CASE WHEN p.MIddlename = '' OR p.MIddlename IS NULL THEN '' 
                         ELSE ' ' + SUBSTRING(p.MIddlename, 1, 1) + '.' END + ' ' + p.Surname AS Name4, 
                s.DesigCode, 
                s.Charges, 
                s.RateDay, 
                p.TelNo, 
                s.RateMon, 
                s.Divisions, 
                s.Sections, 
                s.FromDate, 
                p.Address, 
                s.Renew,
                s.itemNo,
                s.Pages,
                s.StructureID,
                s.ToDate
            FROM dbo.xPersonal p
            INNER JOIN dbo.xService s ON p.ControlNo = s.ControlNo
            WHERE s.FromDate <> s.ToDate
              AND s.Status IN ('REGULAR', 'CO-TERMINOUS', 'ELECTIVE')
              AND s.itemNo IS NOT NULL
              AND s.Pages IS NOT NULL
              AND s.StructureID IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::connection('sqlsrv')->statement("DROP VIEW IF EXISTS dbo.vwActiveAsOf");
    }
};
