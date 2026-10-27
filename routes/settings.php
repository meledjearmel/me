<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('console/settings', '/console/settings/profile');

    Route::get('console/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('console/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('console/settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('console/settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('console/settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('console/settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
