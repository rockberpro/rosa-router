<?php

use Rockberpro\RestRouter\Request;
use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Route;
use Rockberpro\RestRouter\Controllers\AuthController;

Route::prefix('auth')->group(function() {
    Route::post('/refresh', [
        AuthController::class, 'refresh'
    ]);

    Route::post('/access', [
        AuthController::class, 'access'
    ]);
});