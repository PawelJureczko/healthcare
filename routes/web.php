<?php

use App\Http\Controllers\BloodPressureReadingController;
use App\Http\Controllers\BodyMeasurementController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/details', [ProfileController::class, 'updateDetails'])->name('profile.details.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/cialo', [BodyMeasurementController::class, 'index'])->name('body.index');
    Route::post('/body-measurements', [BodyMeasurementController::class, 'store'])->name('body-measurements.store');

    Route::get('/cisnienie', [BloodPressureReadingController::class, 'index'])->name('blood-pressure.index');
    Route::post('/blood-pressure-readings', [BloodPressureReadingController::class, 'store'])->name('blood-pressure-readings.store');
});

require __DIR__.'/auth.php';
