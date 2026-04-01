<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountLookupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/accounts/lookup', AccountLookupController::class)->name('accounts.lookup');

    Route::middleware('account.active')->group(function () {
        Route::post('/friendships', [FriendshipController::class, 'store'])->name('friendships.store');
        Route::patch('/friendships/{friendship}', [FriendshipController::class, 'respond'])->name('friendships.respond');
        Route::delete('/friendships/{friendship}', [FriendshipController::class, 'destroy'])->name('friendships.destroy');
        Route::post('/deposit', [TransactionController::class, 'deposit'])->name('transactions.deposit');
        Route::post('/transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');
        Route::post('/operations/{operation}/reverse', [TransactionController::class, 'reverse'])->name('operations.reverse');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
