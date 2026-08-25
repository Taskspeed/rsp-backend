<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('sqlsrv')->statement("
            CREATE VIEW dbo.vwPlantillaStructureAsOf
            AS
            SELECT        
                vwActiveAsOf.ControlNo, 
                yOffice.Descriptions AS office, 
                yOffice_1.Descriptions AS office2, 
                yDivision_1.Descriptions AS [group], 
                yDivision.Descriptions AS division, 
                ySection.Descriptions AS section, 
                yUnit.Descriptions AS unit, 
                yDesignation.Descriptions AS position, 
                tblStructureDetails.ID, 
                tblStructureDetails.StructureID, 
                tblStructureDetails.OfficeID, 
                tblStructureDetails.OfficeID1, 
                tblStructureDetails.GroupID, 
                tblStructureDetails.DivisionID, 
                tblStructureDetails.SectionID, 
                tblStructureDetails.UnitID, 
                tblStructureDetails.PositionID, 
                tblStructureDetails.PageNo, 
                tblStructureDetails.ItemNo, 
                tblStructureDetails.SG, 
                tblStructureDetails.Ordr, 
                tblStructureDetails.Funded, 
                tblStructureDetails.groupordr, 
                tblStructureDetails.divordr, 
                tblStructureDetails.secordr, 
                tblStructureDetails.unitordr, 
                tblStructureDetails.[level], 
                vwActiveAsOf.Name1, 
                vwActiveAsOf.Status, 
                vwActiveAsOf.Name4, 
                vwActiveAsOf.Pics,
                vwActiveAsOf.FromDate,
                vwActiveAsOf.ToDate
            FROM            
                dbo.tblStructureDetails 
                INNER JOIN dbo.yOffice ON dbo.tblStructureDetails.OfficeID = dbo.yOffice.PMID
                LEFT OUTER JOIN dbo.vwActiveAsOf 
                    ON dbo.vwActiveAsOf.StructureID = dbo.tblStructureDetails.StructureID 
                    AND dbo.vwActiveAsOf.itemNo = dbo.tblStructureDetails.ItemNo 
                    AND dbo.vwActiveAsOf.Pages = dbo.tblStructureDetails.PageNo
                LEFT OUTER JOIN dbo.yDivision AS yDivision_1 ON dbo.tblStructureDetails.GroupID = yDivision_1.PMID 
                LEFT OUTER JOIN dbo.ySection ON dbo.tblStructureDetails.SectionID = dbo.ySection.PMID 
                LEFT OUTER JOIN dbo.yUnit ON dbo.tblStructureDetails.UnitID = dbo.yUnit.PMID 
                LEFT OUTER JOIN dbo.yDivision ON dbo.tblStructureDetails.DivisionID = dbo.yDivision.PMID 
                LEFT OUTER JOIN dbo.yDesignation ON dbo.tblStructureDetails.PositionID = dbo.yDesignation.PMID 
                LEFT OUTER JOIN dbo.yOffice AS yOffice_1 ON dbo.tblStructureDetails.OfficeID1 = yOffice_1.PMID
            WHERE        
                (dbo.tblStructureDetails.StructureID = 4) 
                AND (dbo.yDesignation.Descriptions IS NOT NULL)
        ");
    }

    public function down(): void
    {
        DB::connection('sqlsrv')->statement("DROP VIEW IF EXISTS dbo.vwPlantillaStructureAsOf");
    }
};