<?php

use Rockberpro\RestRouter\Controllers\TestController;
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

Route::prefix('v1')->group(function() {

    Route::get('/test', [TestController::class, 'index']);

});