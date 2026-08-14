<?php

use App\Http\Controllers\Legacy\LegacyPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LegacyPageController::class, 'home']);
Route::get('/home', [LegacyPageController::class, 'home'])->name('home');
Route::get('/{path}', LegacyPageController::class)
    ->where('path', '[A-Za-z0-9\/-]+')
    ->name('legacy.page');
