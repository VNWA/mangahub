<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Custom broadcasting auth route that supports Sanctum token authentication
// This route must be registered BEFORE Laravel's default broadcasting route
// We use Sanctum middleware directly without web middleware to avoid CSRF issues

require __DIR__.'/admin.php';


require __DIR__.'/settings.php';
