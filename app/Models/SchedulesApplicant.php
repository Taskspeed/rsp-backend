<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulesApplicant extends Model
{
    //

    protected $table = 'schedules_applicants';


    protected $fillable = [
        'schedule_id',
        'submission_id'
    ];


    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }
    // public function schedules()
    // {
    //     return $this->belongsTo(Schedule::class,);
    // }
}
