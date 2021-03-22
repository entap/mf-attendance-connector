<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\TimeRecorderLog;
use App\Services\MFAttendanceClient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private function period(Request $request)
    {
        $start = $request->get('start');
        if ($start !== NULL) {
            $start = Carbon::parse($start);
        }
        $end = $request->get('end');
        if ($end !== NULL) {
            $end = Carbon::parse($end);
        }
        if ($start === NULL && $end === NULL) {
            $start = Carbon::today()->firstOfMonth();
            $end = Carbon::today()->lastOfMonth();
        }
        if ($start === NULL) {
            $start = $end->clone()->subMonth();
        }
        if ($end === NULL) {
            $end = $start->clone()->addMonth()->subDay();
        }
        return [$start->toDateString(), $end->toDateString()];
    }

    public function index()
    {
        return Employee::orderBy('id')->get();
    }

    public function view(string $employeeId)
    {
        $employee = Employee::where('id', $employeeId)->orWhere('name', urldecode($employeeId))->firstOrFail();
        return $employee;
    }

    public function attendance(Request $request, string $employeeId)
    {
        $employee = Employee::where('id', $employeeId)->orWhere('name', urldecode($employeeId))->firstOrFail();

        $query = AttendanceRecord::query();
        $query->where('employee_id', $employee->id);
        $query->whereBetween('date', $this->period($request));
        return $query->get();
    }

    public function calendar(Request $request, string $employeeId)
    {
        $employee = Employee::where('id', $employeeId)->orWhere('name', urldecode($employeeId))->firstOrFail();

        // 出勤簿から取得(標準情報)
        $query = AttendanceRecord::query();
        $query->where('employee_id', $employee->id);
        $query->whereBetween('date', $this->period($request));
        $records = $query->get();

        // 申請情報から取得
        $query = AttendanceRequest::query();
        $query->where('employee_id', $employee->id);
        $query->whereBetween('date', $this->period($request));
        $requests = array_column($query->get()->all(), null, "date");

        // カレンダー情報を生成
        $calendar = $records->transform(function ($record) use ($requests) {
            if (isset($requests[$record->date])) {
                return [
                    'date' => $record->date,
                    'is_day_off' => (boolean)$requests[$record->date]->is_day_off,
                    'requested_on' => $requests[$record->date]->requested_on,
                    'comment' => $requests[$record->date]->comment,
                ];
            } else {
                return [
                    'date' => $record->date,
                    'is_day_off' => (boolean)$record->is_day_off,
                ];
            }
        });

        return $calendar;
    }

    public function timeRecorderEvent(string $employeeId, string $event)
    {
        // 従業員を取得
        $employee = Employee::where('id', $employeeId)->orWhere('name', urldecode($employeeId))->firstOrFail();

        // イベントの名前をチェック
        if (!in_array($event, ['checkIn', 'checkOut', 'breaktime', 'breaktimeEnd'])) {
            abort(404);
        }

        // タイムレコーダーを打刻
        $client = new MFAttendanceClient();
        $client->login(env('MF_DOMAIN'), env('MF_USERNAME'), env('MF_PASSWORD'));
        if (!$client->timeRecorderEvent($employee->name, $event)) {
            abort(404);
        }
    }
}
