<?php

use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Core\Route;

Route::prefix('lvl1')->middleware(TestMiddleware::class)->group(function() {

    Route::get('/hello', function() {

        return new Response(
            ['message' => 'Hello World! : from lvl1'],
            Response::OK
        );

    });

    Route::prefix('lvl2')->group(function() {

        Route::get('/status', function() {

            return new Response(
                ['status' => 'API is running : from lvl2'],
                Response::OK
            );

        });

    });

      Route::get('/test', function() {

        return new Response(
            ['message' => 'Test! : from lvl1'],
            Response::OK
        );

    });

});

Route::get('/outside', function() {

    return new Response(
        ['message' => 'Outside route'],
        Response::OK
    );

});