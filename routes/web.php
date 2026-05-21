<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    // Serve the React SPA (not Inertia)
    return view('app');
})->name('home');

Route::get('/dashboard', function () {
    // Serve the React SPA (not Inertia)
    return view('app');
})->name('dashboard');

require __DIR__.'/settings.php';
