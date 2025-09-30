<?php

use App\Filament\Pages\SetPassword;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });


    Route::get('/set-password/{token}', SetPassword::class)
        ->name('set-password')
        ->middleware('guest'); // important, avoid auth redirect
