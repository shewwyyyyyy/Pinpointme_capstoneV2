<?php

use App\Http\Controllers\API\DepartmentApiController;
use App\Http\Controllers\API\LocationApiController;
use App\Http\Controllers\API\PropertyApiController;
use App\Http\Controllers\API\ScanAPIController;
use App\Http\Controllers\API\ScanHistoryApiController;
use App\Http\Controllers\API\ScanProfileApiController;
use App\Http\Controllers\API\EmployeeApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// //profile api
Route::get('/profiles', [ScanProfileApiController::class, 'index']);
Route::post('/profiles/{unique_identifier}', [ScanProfileApiController::class, 'storeUniqueIdentifiers']);
Route::get('/departments', [DepartmentApiController::class, 'index']);
Route::get('/properties', [PropertyApiController::class, 'index']);
Route::get('/locations', [LocationApiController::class, 'index']);
Route::get('/scan-histories', [ScanHistoryApiController::class, 'index']);
Route::get('/employees', [EmployeeApiController::class, 'index']);
