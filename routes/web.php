<?php

use Illuminate\Support\Facades\Route;
use App\Models\Nicho;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/nichos/{nicho}/print', function (Nicho $nicho) {
    $nicho->load(['restaurant', 'customer', 'products']);
    return view('filament.nicho-print', compact('nicho'));
})->middleware(['auth'])->name('nichos.print');
