# MF Attendance Connector

## 概要

MF Attendance Connectorは、MF勤怠をスクレイピングし、読み取りAPIを提供します。

- タイムレコーダーの情報
- 休暇の申請
- 月次の勤怠情報(勤怠の変更申請対応済みの情報)

## 設定

.env.exampleに記載すること。

| .env 変数名 | 説明 |
| --- | --- |
| MF_DOMAIN | MF勤怠の会社ID |
| MF_USERNAME | MF勤怠のユーザ名またはメールアドレス |
| MF_PASSWORD | MF勤怠のパスワード |
| START_HOURS_OF_DAY | 一日の始まりの時間 |

## 同期処理

下記の処理を定期実行すること

### 月次の出退勤の情報を同期する

php artisan mfac:sync-attendance-records {--year=} {--month=}

### 出退勤に関する申請を同期する

php artisan mfac:sync-attendance-requests {--pages=1}

### 従業員情報を同期する

php artisan mfac:sync-employees

### タイムレコーダーの履歴データを同期する

php artisan mfac:sync-time-recorder-logs {--pages=1}

## API

### 従業員一覧

#### リクエスト

/employees

#### レスポンス

```json
[
    {
        "id": 1,
        "name": "全権 管理者",
        "hire_date": "2019-03-05",
        "retire_date": null,
        "sur_name": "全権",
        "given_name": "管理者",
        "is_working": false
    }
]
```

### 従業員詳細

#### リクエスト

/employees/1

#### レスポンス

```json
{
    "id": 21,
    "name": "牧村 美冴",
    "hire_date": "2019-05-01",
    "retire_date": "2020-04-30",
    "sur_name": "牧村",
    "given_name": "美冴",
    "is_working": false
}
```

### 勤怠情報

#### リクエスト

/employees/1/attendance?start=2021-01-01&end=2021-01-31

#### レスポンス

```json
[
    {
        "date": "2021-02-01",
        "working_hours": "09:35:00",
        "breaktime_hours": "00:28:00",
        "time_recorder_logs": [
            {
                "action": "CheckIn",
                "at": "2021-02-01 10:03"
            },
            {
                "action": "Breaktime",
                "at": "2021-02-01 15:02"
            },
            {
                "action": "BreaktimeEnd",
                "at": "2021-02-01 15:30"
            },
            {
                "action": "CheckOut",
                "at": "2021-02-01 20:06"
            }
        ]
    }
]
```

### カレンダー情報

#### リクエスト

/employees/1/calendar?start=2021-01-01&end=2021-01-31

#### レスポンス

```json
[
    {
        "date": "2021-02-01",
        "is_day_off": false
    }
]
```

### 打刻する

#### リクエスト

- /employees/1/checkIn
- /employees/1/checkOut
- /employees/1/breaktime
- /employees/1/breaktimeEnd

#### レスポンス

なし。
