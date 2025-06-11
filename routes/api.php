<?php

use Rockberpro\RestRouter\Core\Route;
use Rockberpro\RestRouter\Controllers\AuthController;

Route::prefix('auth')->group(function() {
    Route::post('/refresh', [
        AuthController::class, 'refresh'
    ]);

    Route::post('/access', [
        AuthController::class, 'access'
    ]);
});