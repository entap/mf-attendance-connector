<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeRecorderEvent extends Model
{
    public const CHECK_IN = 1;
    public const CHECK_OUT = 2;
    public const BREAKTIME = 3;
    public const BREAKTIME_END = 4;
}
