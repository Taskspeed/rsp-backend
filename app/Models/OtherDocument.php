<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherDocument extends Model
{
    //

    protected $table = 'other_documents'; 

    protected $fillable = [
        'document_name',
        'document',
        'nPersonalInfo_id'
    ];
}
