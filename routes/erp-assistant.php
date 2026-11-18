<?php

use App\Http\Controllers\ErpAssistantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','verified','active'])->prefix('assistant')->name('erp-assistant.')->group(function(){
    Route::get('/',[ErpAssistantController::class,'index'])->name('index');
    Route::get('/briefing',[ErpAssistantController::class,'briefing'])->middleware('throttle:30,1')->name('briefing');
    Route::post('/ask',[ErpAssistantController::class,'ask'])->middleware('throttle:12,1')->name('ask');
});
