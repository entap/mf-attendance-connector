<?php

namespace Database\Seeders;

use App\Models\AttendanceRequestType;
use App\Models\TimeRecorderEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TimeRecorderEvent::insert([
            ['name' => '出勤', 'event' => 'CheckIn', 'is_working_hours' => true],
            ['name' => '退勤', 'event' => 'CheckOut', 'is_working_hours' => false],
            ['name' => '休憩開始', 'event' => 'Breaktime', 'is_working_hours' => false],
            ['name' => '休憩終了', 'event' => 'BreaktimeEnd', 'is_working_hours' => true],
        ]);
    }
}
