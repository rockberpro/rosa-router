<?php

use Rockberpro\RosaRouter\Core\Response;
use Rockberpro\RosaRouter\Core\Route;
use Rockberpro\RosaRouter\Controllers\AuthController;

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
