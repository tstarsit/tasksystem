<?php

use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/store',[MessageController::class,'store']);
Route::post('/whats/store',[WhatsAppController::class,'store']);
Route::post('/update',[MessageController::class,'update']);
Route::get('/view',[MessageController::class,'view']);
