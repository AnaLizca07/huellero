<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

Route::prefix('attendance')->group(function () {
    //Sincroniza los datos del huellero
    Route::get('/sync', [AttendanceController::class, 'syncAttendance']); 
    //Obtiene los usuarios 
    Route::get('/users', [AttendanceController::class, 'getUsers']);
    //Obtiene los registros almacenados
    Route::get('/logs', [AttendanceController::class, 'getLogs']);
    // routes/api.php
    Route::get('/test-device', [AttendanceController::class, 'testDevice']);
    Route::get('/test-direct', [AttendanceController::class, 'testUDP']);

});