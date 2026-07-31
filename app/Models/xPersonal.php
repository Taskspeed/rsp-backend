<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class xPersonal extends Model
{
    //

    protected $table = 'xPersonal';

    public function employeeReAssign()
    {
        return $this->hasMany(EmployeeReAssign::class, 'control_no', 'ControlNo');
    }

    public function vwActive()
    {
        return $this->hasOne(vwActive::class, 'ControlNo', 'ControlNo');
    }
}
