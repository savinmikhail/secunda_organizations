<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API docs (Redoc)
Route::get('/docs', function () {
    return view('docs.redoc');
});
