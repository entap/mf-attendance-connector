<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('employee_id');
            $table->string('check_in');
            $table->string('check_out');
            $table->string('breaktime_begin');
            $table->string('breaktime_end');
            $table->time('working_hours');
            $table->time('breaktime_hours');
            $table->boolean('is_day_off');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_records');
    }
}
