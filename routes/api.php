<?php

use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Core\Route;
use Rockberpro\RestRouter\Controllers\AuthController;
use Rockberpro\RestRouter\Middleware\LogRequestMiddleware;

Route::get('/status', function() {
  return new Response([
        'message' => 'API is running'
    ], Response::OK);
});

Route::middleware(LogRequestMiddleware::class)->group(function() {

  Route::get('/name/{name}', function(Request $req) {
    return new Response([
      'message' => "My name is {$req->get('name')}"
    ]);
  });

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
