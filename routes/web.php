<?php

use App\Models\Nicho;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/nichos/{nicho}/print', function (Nicho $nicho) {
    $nicho->load(['restaurant', 'customer', 'products']);

    return view('filament.nicho-print', compact('nicho'));
})->middleware(['auth'])->name('nichos.print');
