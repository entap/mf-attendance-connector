<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeRecorderLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_recorder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('time_recorder_event_id');
            $table->dateTime('at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_recorder_logs');
    }
}
