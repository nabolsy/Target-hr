<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/run-setup', function () {
    Artisan::call('migrate:fresh --seed');
   Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    return 'Done! Config cached, routes cached, views cached, database migrated & seeded. DELETE THIS ROUTE NOWssssssssss.';
});
