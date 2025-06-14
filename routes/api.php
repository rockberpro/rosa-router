<?php

use Rockberpro\RestRouter\Core\Response;
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

Route::prefix('v1')->group(function() {

    Route::get('/hello', function() {

        return new Response(
            ['message' => 'Hello World!'],
            Response::OK
        );

    });

});