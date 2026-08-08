<?php

use Illuminate\Support\Facades\Route;

Route::domain('velora.test')->group(function () {

    Route::get('/domain-test', function () {
        return 'DOMAIN OK';
    });

});

Route::get('/normal-test', function () {
    return 'NORMAL OK';
});
