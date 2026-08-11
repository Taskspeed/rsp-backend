<?php

namespace App\Models;

use App\Models\xService;
use Illuminate\Database\Eloquent\Model;

class TempRegAppointmentReorg extends Model
{
    //

    protected $table = 'tempRegAppointmentReorg';
    protected $primaryKey = 'ControlNo'; // optional, if your primary key is ControlNo
    public $timestamps = false; // if the table doesn’t have created_at/updated_at

    protected $casts = [
        'ControlNo' => 'string',
        // 'assessment_date' => 'date:F d, Y',
        
    ];

    protected $fillable = [
        'deliberation_date',
        'renew',
        'assessment_date'
    ];
    public function vwplantillaStructure()
    {
        return $this->hasOne(VwPlantillaStructure::class, 'ControlNo', 'ControlNo'); // adjust keys
    }

    public function xService()
    {
        return $this->hasOne(xService::class, 'ControlNo', 'ControlNo'); // adjust keys
    }
}
