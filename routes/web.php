<?php

use App\Http\Controllers\BloodPressureReadingController;
use App\Http\Controllers\BodyMeasurementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\GymWorkoutController;
use App\Http\Controllers\LabMarkerController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RunController;
use App\Http\Controllers\SportSessionController;
use App\Http\Controllers\Strava\StravaConnectionController;
use App\Http\Controllers\Strava\StravaSyncController;
use App\Http\Controllers\TrainingGoalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/details', [ProfileController::class, 'updateDetails'])->name('profile.details.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/cialo', [BodyMeasurementController::class, 'index'])->name('body.index');
    Route::post('/body-measurements', [BodyMeasurementController::class, 'store'])->name('body-measurements.store');

    Route::get('/cisnienie', [BloodPressureReadingController::class, 'index'])->name('blood-pressure.index');
    Route::post('/blood-pressure-readings', [BloodPressureReadingController::class, 'store'])->name('blood-pressure-readings.store');

    Route::get('/badania', [LabResultController::class, 'index'])->name('lab-results.index');
    Route::get('/badania/nowe', [LabResultController::class, 'create'])->name('lab-results.create');
    Route::post('/lab-results', [LabResultController::class, 'store'])->name('lab-results.store');
    Route::post('/lab-markers', [LabMarkerController::class, 'store'])->name('lab-markers.store');

    Route::get('/leki', [MedicationController::class, 'index'])->name('medications.index');
    Route::post('/medications', [MedicationController::class, 'store'])->name('medications.store');
    Route::patch('/medications/{medication}', [MedicationController::class, 'update'])->name('medications.update');

    Route::get('/przypomnienia', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::patch('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');

    Route::get('/biegi', [RunController::class, 'index'])->name('runs.index');
    Route::get('/biegi/nowy', [RunController::class, 'create'])->name('runs.create');
    Route::post('/runs', [RunController::class, 'store'])->name('runs.store');
    Route::post('/cele-biegowe', [TrainingGoalController::class, 'store'])->name('training-goals.store');

    Route::get('/sporty', [SportSessionController::class, 'index'])->name('sport-sessions.index');
    Route::get('/sporty/nowy', [SportSessionController::class, 'create'])->name('sport-sessions.create');
    Route::post('/sport-sessions', [SportSessionController::class, 'store'])->name('sport-sessions.store');

    Route::get('/cwiczenia', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::post('/cwiczenia', [ExerciseController::class, 'store'])->name('exercises.store');

    Route::get('/silownia', [GymWorkoutController::class, 'index'])->name('gym-workouts.index');
    Route::get('/silownia/nowy', [GymWorkoutController::class, 'create'])->name('gym-workouts.create');
    Route::post('/gym-workouts', [GymWorkoutController::class, 'store'])->name('gym-workouts.store');

    Route::post('/integracje/strava/synchronizuj', StravaSyncController::class)->name('strava.sync');
    Route::get('/integracje/strava/polacz', [StravaConnectionController::class, 'redirect'])->name('strava.connect');
    Route::get('/integracje/strava/callback', [StravaConnectionController::class, 'callback'])->name('strava.callback');
    Route::delete('/integracje/strava', [StravaConnectionController::class, 'destroy'])->name('strava.disconnect');
});

require __DIR__.'/auth.php';
