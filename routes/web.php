<?php

use App\Filament\Pages\SetPassword;
use App\Filament\Pages\SearchMember;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

    // Route::get('/set-email', SetEmail::class)
    // ->name('set-email')
    // ->middleware('guest');

    Route::get('app/set-password/{token}', SetPassword::class)
        ->name('app/set-password');

    // Route::get('/admin/member-search', SearchMember::class)
    //     ->middleware(['auth', 'verified']);

  
