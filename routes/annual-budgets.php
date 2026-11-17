<?php

use Illuminate\Support\Facades\Route;

// TCH_ANNUAL_BUDGET_ROUTES_V1
Route::middleware(['web', 'auth', 'active'])->prefix('admin/finance/budgets')->name('finance.budgets.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Finance\AnnualBudgetController::class, 'index'])->name('index');
    Route::get('/export.csv', [\App\Http\Controllers\Finance\AnnualBudgetController::class, 'csv'])->name('csv');
    Route::post('/', [\App\Http\Controllers\Finance\AnnualBudgetController::class, 'store'])->name('store');
    Route::get('/{budget}', [\App\Http\Controllers\Finance\AnnualBudgetController::class, 'show'])->whereNumber('budget')->name('show');
    Route::post('/{budget}/revise', [\App\Http\Controllers\Finance\AnnualBudgetController::class, 'revise'])->whereNumber('budget')->name('revise');
});
