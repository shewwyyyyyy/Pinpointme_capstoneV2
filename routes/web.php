<?php

use App\Http\Controllers\AuthController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\EmployeeController;


use Illuminate\Support\Facades\Auth;

// Route::get('/', function () {
//     return view('welcome');
// });


Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
});

Route::post('/login', [AuthController::class, 'login']);


Route::middleware(['auth'])->group(
    function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/errors', function () {
            return Inertia::render('Error', [
                'code' => 500,
                'message' => 'Page not found'
            ]);
        });

        Route::get('/', function () {
            // dd(Auth::user()->profile->property_id);
            if (Auth::user()->isAdmin === 0 && Auth::user()->is_able_to_login === 1) {
                return Inertia::render('Scans');
            }

            return Inertia::render('System/ScanHistories');
        });

        Route::resource('scans', ScanController::class)->except(['create', 'edit', 'show']);
        Route::post('/scans', [ScanController::class, 'scan'])->name('scan');
        //profile
        Route::resource('profiles', ProfileController::class)->except(['create', 'edit', 'show']);
        Route::resource('departments', DepartmentController::class)->except(['create', 'edit', 'show']);
        Route::resource('properties', PropertyController::class)->except(['create', 'edit', 'show']);
        Route::resource('locations', LocationController::class)->except(['create', 'edit', 'show']);
        Route::resource('scan-histories', ScanHistoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('employees', EmployeeController::class)->except(['create', 'edit', 'show']);
    }
);
