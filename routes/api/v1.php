<?php

use App\Http\Controllers\V1\Expenses\ExpenseController;
use Illuminate\Support\Facades\Route;

/*
 * API v1 routes: prefix "api/v1", names "v1.*" and authentication come from routes/api.php.
 */

Route::apiResource('expenses', ExpenseController::class);
