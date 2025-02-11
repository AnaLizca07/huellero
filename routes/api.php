<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ControladorTest;
use App\Http\Controllers\TestController;
use Illuminate\Routing\Controller;

Route::prefix('attendance')->group(function () {
    //Sincroniza los datos del huellero
    Route::get('/sync', [AttendanceController::class, 'syncAttendance']); 
    //Obtiene los usuarios 
    Route::get('/users', [AttendanceController::class, 'getUsers']);
    //Obtiene los registros almacenados
    Route::get('/logs', [AttendanceController::class, 'getLogs']);

    Route::get('/connect', [ControladorTest::class, 'connect']);
    Route::get('/getAttendance', [ControladorTest::class, 'getAttendance']);

    Route::get('/report', [AttendanceController::class, 'getAttendanceReport']);

    //Rutas de prueba
    Route::get('/test-device', [TestController::class, 'testDevice']);
    Route::get('/test-direct', [TestController::class, 'testUDP']);
    Route::get('/test-connectivity', [TestController::class, 'testConnectivity']);
    Route::get('/test-commands', [TestController::class, 'testCommands']);
    Route::get('/device-info', [TestController::class, 'getDeviceInfo']);
    Route::get('/check-zktime', [TestController::class, 'checkZKTime']);
    Route::get('/test-network', [TestController::class, 'testNetwork']);
    Route::get('/firewall', [TestController::class, 'firewall']);
    Route::get('/rawConnection', [TestController::class, 'testRawConnection']);

});