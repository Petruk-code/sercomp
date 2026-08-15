<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\WhatsappTwoFactorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::post('settings/security/whatsapp-2fa/enable', [WhatsappTwoFactorController::class, 'enable'])
        ->middleware('throttle:6,1')
        ->name('security.whatsapp-2fa.enable');

    Route::post('settings/security/whatsapp-2fa/disable', [WhatsappTwoFactorController::class, 'disable'])
        ->middleware('throttle:6,1')
        ->name('security.whatsapp-2fa.disable');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});
