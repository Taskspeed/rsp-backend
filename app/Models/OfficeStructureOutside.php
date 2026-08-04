<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeStructureOutside extends Model
{
    //

    protected $table = 'office_structre_outsides';

    protected $fillable = [
        'lib_office_id',
        'office',
        'office2',
        'group',
        'division',
        'section',
        'unit',
    ];

    protected $casts = [
        'lib_office_id' => 'integer',
    ];
}
