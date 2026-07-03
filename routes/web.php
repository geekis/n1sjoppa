<?php

use App\Support\AdminNavigation;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Kiosk (no auth — opened via QR)
Route::livewire('/kiosk', 'pages::kiosk.index')->name('kiosk');
Route::livewire('/kiosk/staff', 'pages::kiosk.staff')->name('kiosk.staff');

// Awaiting-approval landing (authenticated, but NOT gated by approval).
Route::livewire('/pending', 'pages::pending')->middleware('auth')->name('pending');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');
});

// Admin / back-office — requires an approved account plus the relevant permission.
Route::middleware(['auth', 'approved'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect(AdminNavigation::firstAllowedUrl(request()->user())));

    Route::livewire('reports', 'pages::admin.reports')->middleware('permission:view reports')->name('reports');
    Route::livewire('products', 'pages::admin.products')->middleware('permission:manage products')->name('products');
    Route::livewire('categories', 'pages::admin.categories')->middleware('permission:manage categories')->name('categories');
    Route::livewire('staff', 'pages::admin.staff')->middleware('permission:manage kiosk staff')->name('staff');
    Route::livewire('users', 'pages::admin.users')->middleware('permission:manage users')->name('users');
});

require __DIR__.'/settings.php';
