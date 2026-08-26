<?php

/*
|--------------------------------------------------------------------------
| SolarTools - Web Routes
|--------------------------------------------------------------------------
| Prefixed by /extensions/solartools/
| Standard web routes (no API auth required, uses session auth).
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

// Web routes placeholder - primary functionality uses client API routes.
// This file is required by conf.yml but can be left minimal.

Route::get('/', function () {
    return redirect('/');
});
