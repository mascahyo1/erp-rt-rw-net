<?php

use Illuminate\Support\Facades\Route;

Route::prefix('error-test')->group(function () {
    Route::get('403', function () {
        abort(403);
    });

    Route::get('404', function () {
        abort(404);
    });

    Route::get('500', function () {
        abort(500);
    });

    Route::get('503', function () {
        abort(503);
    });
});
