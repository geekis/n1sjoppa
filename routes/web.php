<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Kiosk (no auth — opened via QR)
Route::livewire('/kiosk', 'pages::kiosk.index')->name('kiosk');
Route::livewire('/kiosk/staff', 'pages::kiosk.staff')->name('kiosk.staff');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Admin / back-office (single admin login)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/reports');
    Route::livewire('reports', 'pages::admin.reports')->name('reports');
    Route::livewire('products', 'pages::admin.products')->name('products');
    Route::livewire('categories', 'pages::admin.categories')->name('categories');
    Route::livewire('staff', 'pages::admin.staff')->name('staff');
});

require __DIR__.'/settings.php';
