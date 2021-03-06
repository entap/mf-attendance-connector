<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\MFAttendanceClient;
use Illuminate\Console\Command;

class SyncEmployeesCommand extends Command
{
    protected $signature = 'mfac:sync-employees';
    protected $description = '従業員情報を同期する';

    private function sync($rows)
    {
        foreach ($rows as $row) {
            $attributes = [
                'name' => $row['name'],
            ];
            Employee::updateOrCreate($attributes, $row);
        }
    }

    public function handle()
    {
        $client = new MFAttendanceClient();
        $client->login(env('MF_DOMAIN'), env('MF_USERNAME'), env('MF_PASSWORD'));
        $this->sync($client->exportEmployees());
    }
}
