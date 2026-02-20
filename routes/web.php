<?php

use App\Http\Controllers\ForcePasswordChangeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->group(function (): void {
    Route::get('/force-password-change', [ForcePasswordChangeController::class, 'edit'])
        ->name('password.force.edit');
    Route::put('/force-password-change', [ForcePasswordChangeController::class, 'update'])
        ->name('password.force.update');
});
