<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeRecorderLog extends Model
{
    public $timestamps = false;
    public $fillable = [
        'name',
        'employee_id',
        'time_recorder_event_id',
        'at',
    ];
    protected $hidden = [
        'id',
        'employee_id',
        'time_recorder_event_id',
    ];
    protected $appends = [
        'event',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function timeRecorderEvent()
    {
        return $this->belongsTo(TimeRecorderEvent::class);
    }

    public function getEventAttribute()
    {
        return $this->timeRecorderEvent->event;
    }
}
