<?php

use Rockberpro\RestRouter\Response;
use Rockberpro\RestRouter\Route;

Route::prefix('v1')->group(function() {

    Route::get('/hello', function() {
        return new Response([
            'message' => 'Hello World'
        ], 200);
    });

});