<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public $fillable = [
        'name',
        'hire_date',
        'retire_date',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    protected $appends = [
        'sur_name',
        'given_name',
        'is_working_hours',
        'is_hiring',
    ];

    public function timeRecorderLogs()
    {
        return $this->hasMany(TimeRecorderLog::class);
    }

    public function holidays()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function getSurNameAttribute()
    {
        $names = explode(' ', $this->name);
        return $names[0];
    }

    public function getGivenNameAttribute()
    {
        $names = explode(' ', $this->name);
        return $names[1];
    }

    public function getIsWorkingHoursAttribute()
    {
        if ($this->is_hiring == false) {
            return false;
        }
        $timeRecorderLog = TimeRecorderLog::where('employee_id', $this->id)->orderBy('at', 'desc')->first();
        if ($timeRecorderLog === NULL) {
            return false;
        } else {
            return (boolean)$timeRecorderLog->timeRecorderEvent->is_working_hours;
        }
    }

    public function getIsHiringAttribute()
    {
        $today = date('Y-m-d');
        if ($this->hire_date === NULL || $this->retire_date === NULL) {
            return true;
        } else {
            return $this->hire_date <= $today && $today <= $this->retire_date;
        }
    }

    public function setHireDateAttribute($val)
    {
        $this->attributes['hire_date'] = $val === '' ? NULL : $val;
    }

    public function setRetireDateAttribute($val)
    {
        $this->attributes['retire_date'] = $val === '' ? NULL : $val;
    }
}
