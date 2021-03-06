<?php

namespace App\Console\Commands;

use App\Models\TimeRecorderLog;
use App\Models\AttendanceRequestType;
use App\Models\Employee;
use App\Models\AttendanceRequest;
use App\Services\MFAttendanceClient;
use Illuminate\Console\Command;

class SyncAttendanceRequestsCommand extends Command
{
    protected $signature = 'mfac:sync-attendance-requests {--pages=1}';
    protected $description = '出退勤に関する申請を同期する';

    private function fetch(MFAttendanceClient $client, int $pages = 1)
    {
        $rows = [];
        for ($page = 1; $page <= $pages; $page++) {
            $rows = array_merge($rows, $client->workflowRequests('approved', $page));
        }
        return $rows;
    }

    private function sync($rows)
    {
        foreach ($rows as $row) {
            $employee = Employee::firstOrCreate(['name' => $row['employee']]);
            $created = AttendanceRequest::firstOrCreate([
                'employee_id' => $employee->id,
                'date' => $row['date'],
                'requested_on' => $row['requested_on'],
                'type' => $row['type'],
                'comment' => $row['comment'],
            ]);
            if (!$created->wasRecentlyCreated) {
                return; // 以後は保存済みなので終了
            }
        }
    }

    public function handle()
    {
        $client = new MFAttendanceClient();
        $client->login(env('MF_DOMAIN'), env('MF_USERNAME'), env('MF_PASSWORD'));

        $pages = $this->option('pages');
        $this->sync($this->fetch($client, $pages));
    }
}
