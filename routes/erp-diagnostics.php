<?php

use App\Http\Controllers\Admin\ErpDiagnosticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','verified','active'])->prefix('admin/diagnostics')->name('admin.erp-diagnostics.')->group(function() {
    Route::get('/',[ErpDiagnosticsController::class,'index'])->name('index');
    Route::post('/repair',[ErpDiagnosticsController::class,'repair'])->name('repair');
    Route::post('/resolve',[ErpDiagnosticsController::class,'resolve'])->name('resolve');
    Route::post('/tally-connection',[ErpDiagnosticsController::class,'tally'])->name('tally');
});
