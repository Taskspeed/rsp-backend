<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibOffice extends Model
{
    //
      protected $table = 'lib_offices';


    protected $fillable = [
        'office_name'
    ];

    protected $casts = [
        'officeId' => 'integer',
    
    ];


    public function officeStructureOutside()
    {
        return $this->hasMany(OfficeStructureOutside::class, 'lib_office_id', 'id');
    }
}
