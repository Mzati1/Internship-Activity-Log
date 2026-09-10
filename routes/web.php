<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WeeklyLogController;

Route::get('/', [WeeklyLogController::class, 'index'])->name('dashboard');
Route::get('/log/create', [WeeklyLogController::class, 'create'])->name('weekly-log.create');
Route::post('/weekly-log', [WeeklyLogController::class, 'store'])->name('weekly-log.store');
Route::get('/weekly-log-data/{week}', [WeeklyLogController::class, 'getWeekData'])->name('weekly-log.data');
Route::post('/weekly-log/daily', [WeeklyLogController::class, 'storeDaily'])->name('weekly-log.daily.store');
Route::post('/weekly-log/daily/update', [WeeklyLogController::class, 'updateDaily'])->name('weekly-log.daily.update');
Route::post('/weekly-log/daily/delete', [WeeklyLogController::class, 'deleteDaily'])->name('weekly-log.daily.delete');
Route::post('/profile/update', [WeeklyLogController::class, 'updateProfile'])->name('profile.update');
