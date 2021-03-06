<?php

namespace App\Console\Commands;

use App\Models\TimeRecorderEvent;
use App\Models\TimeRecorderLog;
use App\Models\AttendanceRequestType;
use App\Models\Employee;
use App\Services\MFAttendanceClient;
use Illuminate\Console\Command;

class SyncTimeRecorderLogsCommand extends Command
{
    protected $signature = 'mfac:sync-time-recorder-logs {--pages=1}';
    protected $description = 'タイムレコーダーの履歴データを同期する';

    private function fetch(MFAttendanceClient $client, string $link, int $pages = 1)
    {
        $rows = [];
        for ($page = 1; $page <= $pages; $page++) {
            $rows = array_merge($rows, $client->timeRecorderLogs($link, $page));
        }
        return $rows;
    }

    private function sync($rows)
    {
        foreach ($rows as $row) {
            $employee = Employee::firstOrCreate(['name' => $row['employee']]);
            $event = TimeRecorderEvent::where('name', $row['type'])->first();
            $created = TimeRecorderLog::firstOrCreate([
                'employee_id' => $employee->id,
                'time_recorder_event_id' => $event->id,
                'at' => $row['time'],
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
        $timeRecorders = $client->timeRecorders();
        $pages = $this->option('pages');
        foreach ($timeRecorders as $timeRecorder) {
            $this->sync($this->fetch($client, $timeRecorder['link'], $pages));
        }
    }
}
