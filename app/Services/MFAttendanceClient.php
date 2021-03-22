<?php

namespace App\Services;

use Goutte\Client;
use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\LexerConfig;

class MFAttendanceClient
{
    protected Client $client;

    /**
     * MfAttendanceClient constructor.
     */
    function __construct()
    {
        $this->client = new Client();
    }

    /**
     * HTTPリクエストを実行し、結果をCrawlerで取得する
     *
     * @param $method
     * @param $path
     * @param array $params
     * @return \Symfony\Component\DomCrawler\Crawler|null
     */
    private function request($method, $path, $params = [])
    {
        return $this->client->request($method, 'https://attendance.moneyforward.com' . $path, $params);
    }

    /**
     * HTTPリクエストを実行し、結果をテキストで取得する
     *
     * @param $method
     * @param $path
     * @param array $params
     * @return string
     */
    private function requestText($method, $path, $params = [])
    {
        $this->request($method, $path, $params);
        return $this->client->getInternalResponse()->getContent();
    }

    /**
     * HTTPリクエストを実行し、結果をJSONで取得する
     *
     * @param $method
     * @param $path
     * @param array $params
     * @return array
     */
    private function requestJson($method, $path, $params = [])
    {
        return json_decode($this->requestText($method, $path, $params));
    }

    /**
     * HTTPリクエストを実行し、結果をCSVで取得する
     *
     * @param $method
     * @param $path
     * @param array $params
     * @return array
     */
    private function requestCsv($method, $path, $params = [])
    {
        // CSVファイルのダウンロード
        $content = $this->requestText($method, $path);

        // CSVファイルを一時ファイルに保存
        $tempnam = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempnam, $content);

        // CSVファイルのパース
        $data = [];
        $config = new LexerConfig();
        $lexer = new Lexer($config);
        $interpreter = new Interpreter();
        $interpreter->addObserver(function (array $row) use (&$data) {
            $data[] = $row;
        });
        $lexer->parse($tempnam, $interpreter);

        // 一時ファイルを削除
        unlink($tempnam);

        return $data;
    }

    /**
     * MF勤怠のエクスポーターを待機する。
     *
     * @param $uri
     * @param $retry
     * @return bool
     */
    private function waitExporter($uri, $retry)
    {
        while ($retry-- > 0) {
            $json = $this->requestJson('GET', $uri);
            if ($json->exporter->state === 'succeeded') {
                return true;
            }
            sleep(1);
        }
        return false;
    }

    /**
     * 日本語の日付を書式化する
     *
     * @param $str
     * @return mixed
     */
    private function normalizeDatetime($str)
    {
        return str_replace(['年', '月', '日', '/'], ['-', '-', '', '-'], $str);
    }

    /**
     * MF勤怠にログインする
     *
     * @param string $office_account_name 会社ID
     * @param string $account_name_or_email アカウントID/メールアドレス
     * @param string $password パスワード
     * @return bool
     */
    public function login(string $office_account_name, string $account_name_or_email, string $password)
    {
        $crawler = $this->request('GET', '/employee_session/new');
        $form = $crawler->selectButton('ログイン')->form();
        $crawler = $this->client->submit($form, [
            'employee_session_form' => [
                'office_account_name' => $office_account_name,
                'account_name_or_email' => $account_name_or_email,
                'password' => $password,
            ]
        ]);

        $loginError = $crawler->filter('ul.attendance-form-notification');
        if ($loginError->count() > 0) {
            //$loginError->->text();
            return false;
        }
        return true;
    }

    /**
     * タイムレコーダーの一覧を取得する。
     *
     * @return array タイムレコーダーの一覧
     */
    public function timeRecorders()
    {
        $timeRecorders = [];
        $crawler = $this->request('GET', '/admin/settings/time_recorders');
        $crawler->filter('table.attendance-table-contents')->eq(0)->filter('tr')->each(function ($tr) use (&$timeRecorders) {
            $tds = $tr->filter('td');
            if ($tds->count() > 0) {
                $timeRecorders[] = [
                    'name' => $tds->eq(0)->text(),
                    'link' => $tds->eq(1)->filter('a')->eq(0)->attr('href'),
                ];
            }
        });
        return $timeRecorders;
    }

    /**
     * タイムレコーダーの履歴データを取得する
     *
     * @return array タイムレコーダーの履歴データ
     */
    public function timeRecorderLogs($link, $page = 1)
    {
        $crawler = $this->request('GET', $link . '?page=' . $page);
        $tables = $crawler->filter('table.attendance-table-contents');

        $indexes = [];
        $tables->eq(0)->filter('th')->each(function ($td) use (&$indexes) {
            $indexes[$td->text()] = count($indexes);
        });

        $data = [];
        $tables->eq(1)->filter('tr')->each(function ($tr) use (&$data, $indexes) {
            $tds = $tr->filter('td');
            $data[] = [
                'employee' => $tds->eq($indexes['従業員'])->text(),
                'type' => $tds->eq($indexes['打刻種別'])->text(),
                'time' => $this->normalizeDatetime($tds->eq($indexes['打刻時間'])->text()),
            ];
        });
        return $data;
    }

    /**
     * 出勤簿データを取得する
     *
     * @return array タイムレコーダーの履歴データ
     */
    public function exportDailyAttendanceItems($year, $month)
    {
        // 出勤簿のエクスポートページを開く
        $crawler = $this->request('GET', '/admin/settings/exporters/daily_attendance_item_csv_exporters/new');
        $csrf = $crawler->filter('meta[name=csrf-token]')->eq(0)->attr('content');

        // エクスポートボタンを押す
        $json = $this->requestJson('POST', '/admin/settings/exporters/daily_attendance_item_csv_exporters', [
            'authenticity_token' => $csrf,
            'admin_settings_exporters_daily_attendance_item_csv_exporter_form' => [
                'year' => $year,
                'month' => $month,
                'cutoff_day_value' => '',
                'attendance_time_format' => 'hh_mm',
                'rounding_method' => 'floor',
            ],
        ]);
        $exporterUri = $json->exporter->url;

        // エクスポートするファイルを待つ(リトライは10回まで)
        if (!$this->waitExporter($exporterUri, 10)) {
            return false; // ダウンロード準備が整わなかった
        }

        // CSVファイルのダウンロード
        $csv = $this->requestCsv('GET', $exporterUri . '/download?file_type=utf8_csv');

        // 結果の整理
        $result = [];
        $header = array_shift($csv);
        $indexes = array_flip($header);
        foreach ($csv as $row) {
            $result[] = [
                'employee' => $row[$indexes['氏名']],
                'date' => $row[$indexes['日付']],
                'check_in' => $row[$indexes['出勤']],
                'check_out' => $row[$indexes['退勤']],
                'breaktime_begin' => $row[$indexes['休憩入り']],
                'breaktime_end' => $row[$indexes['休憩戻り']],
                'working_hours' => $row[$indexes['総労働時間']],
                'breaktime_hours' => $row[$indexes['休憩']],
                'is_day_off' => $row[$indexes['勤怠区分']] == '平日' ? false : true,
            ];
        }
        return $result;
    }

    /**
     * 従業員データを取得する
     *
     * @return array タイムレコーダーの履歴データ
     */
    public function exportEmployees()
    {
        // 出勤簿のエクスポートページを開く
        $crawler = $this->request('GET', '/admin/settings/exporters/employees_csv_exporters/new');
        $csrf = $crawler->filter('meta[name=csrf-token]')->eq(0)->attr('content');

        // エクスポートボタンを押す
        $json = $this->requestJson('POST', '/admin/settings/exporters/employees_csv_exporters', [
            'authenticity_token' => $csrf,
            'admin_settings_exporters_employees_csv_exporter_form' => [
                'with_retired_employees' => true,
                'with_not_joined_employees' => true,
            ],
        ]);
        $exporterUri = $json->exporter->url;

        // エクスポートするファイルを待つ(リトライは10回まで)
        if (!$this->waitExporter($exporterUri, 10)) {
            return false; // ダウンロード準備が整わなかった
        }

        // CSVファイルのダウンロード
        $csv = $this->requestCsv('GET', $exporterUri . '/download?file_type=utf8_csv');

        // 結果の整理
        $result = [];
        $header = array_shift($csv);
        $indexes = array_flip($header);
        foreach ($csv as $row) {
            $result[] = [
                'name' => $row[$indexes['苗字']] . ' ' . $row[$indexes['名前']],
                'hire_date' => $row[$indexes['入社年月日']],
                'retire_date' => $row[$indexes['退職年月日']],
            ];
        }
        return $result;
    }

    /**
     * 受信ワークフローを取得する
     *
     * @param $type approved=承認済み
     * @return array 受信ワークフローのデータ
     */
    public function workflowRequests($type, $page = 1)
    {
        $data = [];
        $uri = '/admin/workflow_requests/' . $type . '?page=' . $page;
        $uri .= '&filter_form[workflow_type_in][]=WorkflowRequest::AbsenceWorkflowRequest';
        $uri .= '&filter_form[workflow_type_in][]=WorkflowRequest::HolidayWorkWorkflowRequest';
        $uri .= '&filter_form[workflow_type_in][]=WorkflowRequest::LeaveWorkflowRequest';
        $crawler = $this->request('GET', $uri);
        $table = $crawler->filter('table.attendance-table-contents')->eq(1);
        $table->filter('tr')->each(function ($tr) use (&$data) {
            $tds = $tr->filter('td');
            $data[] = [
                'requested_on' => $this->normalizeDatetime($tds->eq(0)->text()),
                'employee' => $tds->eq(1)->text(),
                'type' => $tds->eq(3)->text(),
                'date' => $this->normalizeDatetime($tds->eq(4)->text()),
                'comment' => $tds->eq(5)->text(),
            ];
        });
        return $data;
    }

    /**
     * タイムレコーダーモードで、打刻する
     */
    public function timeRecorderEvent($name, $event)
    {
        // タイムレコーダーモードに移行する
        $crawler = $this->request('GET', '/time_recorder_mode/session/new');
        $form = $crawler->selectButton('開始')->form();
        $crawler = $this->client->submit($form, [
            'time_recorder_session_form' => [
                'mode' => 'web_with_pasori',
            ]
        ]);

        // タイムレコーダーモードの画面から、JSONデータを抜き出して、
        // 該当する従業員のデータを取得する
        $data = json_decode($crawler->filter('script[data-target="time-recorder-mode--time-recorder-mode.json"]')->text());
        $targetEmployee = NULL;
        foreach ($data->employees as $employee) {
            if (html_entity_decode($employee->name) === $name) {
                $targetEmployee = $employee;
                break;
            }
        }
        if ($targetEmployee === NULL) {
            return false; // 見つからなかった
        }

        // イベントの名前
        if ($event === 'checkIn') {
            $event = 'clock_in';
        } else if ($event === 'checkOut') {
            $event = 'clock_out';
        } elseif ($event === 'breaktime') {
            $event = 'start_break';
        } elseif ($event === 'breaktimeEnd') {
            $event = 'end_break';
        } else {
            return false;
        }

        // 打刻
        $this->client->request('POST', $data->web_timestamp_api_data->url, [
            'attendance_time_recorder_id' => $data->web_timestamp_api_data->time_recorder_id,
            'device_name' => $data->web_timestamp_api_data->time_recorder_name,
            'employee_id' => $targetEmployee->id,
            'event' => $event,
            'office_location_id' => $data->web_timestamp_api_data->office_location_id,
            'secret_key' => $data->web_timestamp_api_data->secret_key,
            'time' => date('c'),
        ]);

        // 結果
        $result = json_decode($this->client->getInternalResponse()->getContent());
        return $result->state === 'success';
    }
}
