<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pds extends Model
{
    //

     protected $table = 'pds'; 

    protected $fillable = [
        'pds_name',
        'pds_file',
        'nPersonalInfo_id'
    ];
}
