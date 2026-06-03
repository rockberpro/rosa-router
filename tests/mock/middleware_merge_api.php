<?php

use Rockberpro\RestRouter\Core\Response;
use Rockberpro\RestRouter\Core\Route;

// Outer group logs everything; inner group adds auth.
// A route inside the inner group must inherit BOTH, outer-most first.
Route::prefix('merge')->middleware(OuterMiddleware::class)->group(function() {

    Route::get('/plain', function() {
        return new Response(['ok' => true], Response::OK);
    });

    Route::middleware(InnerMiddleware::class)->group(function() {

        Route::get('/guarded', function() {
            return new Response(['ok' => true], Response::OK);
        });

        // same middleware as the outer group declared again -> must dedupe
        Route::middleware(OuterMiddleware::class)->group(function() {
            Route::get('/dup', function() {
                return new Response(['ok' => true], Response::OK);
            });
        });

    });

});
