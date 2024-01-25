<?php

use App\Http\Controllers\DashboardController;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\TenantsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {

    echo 1234567890;
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
});


Route::get('/123', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//Route::resource('/people', PeopleController::class)->except(['show']);

require __DIR__.'/auth.php';
//Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//Route::name('system')->resource('/tenants', 'TenantsController');

Route::name('central')->resource('/tenants', TenantsController::class);