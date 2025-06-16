<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return \redirect('admin/login');
});

Route::get('/view',[\App\Http\Controllers\TestController::class,'index'] );
Route::get('/lab-report/download/{code}', [\App\Http\Controllers\TestController::class, 'downloadPDF'])->name('report.download');



