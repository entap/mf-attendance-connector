<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AttendanceRecord extends Model
{
    public $fillable = [
        'date',
        'employee_id',
        'check_in',
        'check_out',
        'breaktime_begin',
        'breaktime_end',
        'working_hours',
        'breaktime_hours',
        'is_day_off',
    ];
    protected $appends = [
        'time_recorder_logs',
    ];
    protected $hidden = [
        'id',
        'employee_id',
        'check_in',
        'check_out',
        'breaktime_begin',
        'breaktime_end',
        'is_day_off',
        'created_at',
        'updated_at',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    private function strToTimeRecorderLogs($str, $event)
    {
        $start_time_of_day = sprintf('%02d:00:00', env('START_HOURS_OF_DAY', 0));
        $next_date = Carbon::create($this->date)->addDay()->toDateString();

        $rows = [];
        if ($str !== '') {
            foreach (explode(' ', $str) as $time) {
                $datetime = $time < $start_time_of_day ? $next_date : $this->date;
                $datetime .= ' ' . $time . ':00';
                $rows[] = [
                    'event' => $event,
                    'at' => $datetime,
                ];
            }
        }
        return $rows;
    }

    public function getTimeRecorderLogsAttribute()
    {
        if ($this->date == date('Y-m-d')) {
            // 本日の場合には、タイムレコーダーの履歴データから取得する
            $start_hours_of_day = env('START_HOURS_OF_DAY', 0);
            $today = Carbon::today()->addHours($start_hours_of_day);
            $tomorrow = Carbon::today()->addDay()->addHours($start_hours_of_day);
            $query = TimeRecorderLog::query();
            $query->where('employee_id', $this->employee_id);
            $query->whereBetween('at', [$today, $tomorrow]);
            $query->orderBy('at');
            return $query->get()->toArray();
        } else {
            // 本日以外は、勤怠簿から取得する
            $logs = array_merge(
                $this->strToTimeRecorderLogs($this->check_in, 'CheckIn'),
                $this->strToTimeRecorderLogs($this->check_out, 'CheckOut'),
                $this->strToTimeRecorderLogs($this->breaktime_begin, 'Breaktime'),
                $this->strToTimeRecorderLogs($this->breaktime_end, 'BreaktimeEnd')
            );
            usort($logs, function ($a, $b) {
                return strcmp($a['at'], $b['at']);
            });
            return $logs;
        }
    }
}
