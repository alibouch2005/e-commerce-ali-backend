<?php
// traitements

use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*|--------------------------------------------------------------------------
    | Password Reset Routes
    |--------------------------------------------------------------------------*/

Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset']);
