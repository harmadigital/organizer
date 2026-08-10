<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\PermissionController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::name('dashboard.')->middleware(['auth:web', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('index');
});

Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update_ajax')->middleware('auth');

Route::name('master.')->prefix('master')->middleware(['auth:web', config('jetstream.auth_session'), 'verified'])->group(function () {
    // user
    Route::name('user.')->controller(UserController::class)->prefix('user')->group(function () {
        Route::get('/index', 'index')->middleware('can:users.view')->name('index');
        Route::get('/data', 'data')->middleware('can:users.view')->name('data');
        Route::get('/show/{user:id}', 'show')->middleware('can:users.view')->name('show');
        Route::post('/insert', 'insert')->middleware('can:users.create')->name('insert');
        Route::post('/update/{user:id}', 'update')->middleware('can:users.update')->name('update');
        Route::post('/delete/{user:id}', 'delete')->middleware('can:users.delete')->name('delete');
        Route::post('/reset_2fa/{user:id}', 'reset_2fa')->middleware('can:users.reset-two-factor')->name('reset_2fa');
    });

    // role
    Route::name('role.')->controller(RoleController::class)->prefix('role')->group(function () {
        Route::get('/index', 'index')->middleware('can:roles.view')->name('index');
        Route::get('/data', 'data')->middleware('can:roles.view')->name('data');
        Route::get('/detail/{role:id}', 'detail')->middleware('can:roles.view')->name('detail');
        Route::post('/insert', 'insert')->middleware('can:roles.create')->name('insert');
        Route::post('/update/{role:id}', 'update')->middleware('can:roles.update')->name('update');
        Route::post('/delete/{role:id}', 'delete')->middleware('can:roles.delete')->name('delete');
    });

    // permission
    Route::name('permission.')->controller(PermissionController::class)->prefix('permission')->group(function () {
        Route::get('/index', 'index')->middleware('can:permissions.view')->name('index');
        Route::get('/data', 'data')->middleware('can:permissions.view')->name('data');
        Route::get('/detail/{permission:id}', 'detail')->middleware('can:permissions.view')->name('detail');
        Route::post('/insert', 'insert')->middleware('can:permissions.create')->name('insert');
        Route::post('/update/{permission:id}', 'update')->middleware('can:permissions.update')->name('update');
        Route::post('/delete/{permission:id}', 'delete')->middleware('can:permissions.delete')->name('delete');
    });
});
