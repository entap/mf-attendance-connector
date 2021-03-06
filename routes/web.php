<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Services\MFAttendanceClient;

$router->get('employees', 'EmployeeController@index');
$router->get('employees/{employeeId}', 'EmployeeController@view');
$router->get('employees/{employeeId}/attendance', 'EmployeeController@attendance');
$router->get('employees/{employeeId}/calendar', 'EmployeeController@calendar');
$router->post('employees/{employeeId}/{event}', 'EmployeeController@timeRecorderEvent');

$router->get('/', function () {
    $client = new MFAttendanceClient();
    $client->login(env('MF_DOMAIN'), env('MF_USERNAME'), env('MF_PASSWORD'));
    $client->timeRecorderEvent('松岡 利昌', 'breaktimeEnd');
});
