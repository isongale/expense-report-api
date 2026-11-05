<?php

use Illuminate\Support\Facades\Route;

/*
 * API versions. Laravel adds the "api" prefix and middleware group (bootstrap/app.php);
 * each version has its own prefix, route names and routes file.
 */

Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:sanctum')
    ->group(base_path('routes/api/v1.php'));
