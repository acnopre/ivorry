<?php

use App\Filament\Pages\Auth\SetPassword;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/reset-password/{token}', SetPassword::class)->name('password.reset');
