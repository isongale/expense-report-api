<?php

use App\Http\Controllers\V1\Expenses\ExpenseController;
use Illuminate\Support\Facades\Route;

/*
 * API v1 routes: prefix "api/v1", names "v1.*" and authentication come from routes/api.php.
 */

// Updates are partial (PATCH only): PUT would promise a full replacement that the rules do not enforce.
Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'show', 'destroy']);
Route::patch('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
