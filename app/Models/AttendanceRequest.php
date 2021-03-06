<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    public $timestamps = false;
    public $fillable = [
        'employee_id',
        'date',
        'requested_on',
        'type',
        'comment',
    ];
    protected $appends = [
        'is_day_off',
    ];
    protected $hidden = [
        'id',
        'employee_id',
        'type',
        'created_at',
        'updated_at',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getIsDayOffAttribute()
    {
        return $this->type == '休日出勤申請' ? false : true;
    }
}
