<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);
Route::get('/home', HomeController::class)->name('home');
Route::get('/{path}', LegacyPageController::class)
    ->where('path', '[A-Za-z0-9\/-]+')
    ->name('legacy.page');
