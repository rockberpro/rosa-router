<?php

use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Core\Route;
use Rockberpro\RestRouter\Controllers\AuthController;

Route::get('/status', function() {
  return new Response([
        'message' => 'API is running'
    ], Response::OK);
});

Route::head('/', function() {
    return new Response([], Response::OK);
});

Route::options('/', function() {
    return new Response();
});

Route::prefix('auth')->group(function() {
    Route::post('/refresh', [
        AuthController::class, 'refresh'
    ]);

    Route::post('/access', [
        AuthController::class, 'access'
    ]);
});
