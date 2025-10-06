<?php

use Illuminate\Support\Facades\Route;

// ルートパスでReactアプリを配信
Route::get('/', function () {
    return response()->file(public_path('app/index.html'));
});
