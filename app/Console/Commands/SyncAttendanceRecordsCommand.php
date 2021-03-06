<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\MFAttendanceClient;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAttendanceRecordsCommand extends Command
{
    protected $signature = 'mfac:sync-attendance-records {--year=} {--month=}';
    protected $description = '出退勤記録を同期する';

    private function fetch(MFAttendanceClient $client)
    {
        $year = $this->option('year');
        $month = $this->option('month');
        if ($year === NULL || $month === NULL) {
            $yesterday = new Carbon('yesterday');
            $year = $yesterday->year;
            $month = $yesterday->month;
        }
        return $client->exportDailyAttendanceItems($year, $month);
    }

    private function sync($rows)
    {
        foreach ($rows as $row) {
            $employee = Employee::firstOrCreate(['name' => $row['employee']]);
            $attributes = [
                'date' => $row['date'],
                'employee_id' => $employee->id,
            ];
            AttendanceRecord::updateOrCreate($attributes, $row);
        }
    }

    public function handle()
    {
        $client = new MFAttendanceClient();
        $client->login(env('MF_DOMAIN'), env('MF_USERNAME'), env('MF_PASSWORD'));
        $this->sync($this->fetch($client));
    }
}
