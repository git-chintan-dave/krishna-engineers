<?php

use App\Http\Controllers\Settings\DownloadDatabaseSnapshotController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Settings
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::controller(ProfileController::class)
        ->prefix('settings/profile')
        ->group(function () {
            Route::get('/', 'edit')
                ->name('profile.edit');

            Route::patch('/', 'update')
                ->name('profile.update');
        });
});

/*
|--------------------------------------------------------------------------
| Verified Settings
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete(
        'settings/profile',
        [ProfileController::class, 'destroy'],
    )->name('profile.destroy');

    Route::get(
        'settings/security',
        [SecurityController::class, 'edit'],
    )
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put(
        'settings/password',
        [SecurityController::class, 'update'],
    )
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')
        ->name('appearance.edit');
});

/*
|--------------------------------------------------------------------------
| Database Backup
|--------------------------------------------------------------------------
*/

Route::get(
    'settings/database/download',
    DownloadDatabaseSnapshotController::class,
)
    ->middleware('auth')
    ->name('settings.database.download');

/*
|--------------------------------------------------------------------------
| Passkey Discovery
|--------------------------------------------------------------------------
*/

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
