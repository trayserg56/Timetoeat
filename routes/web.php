<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordWithCodeController;
use App\Http\Controllers\Auth\PasswordResetCodeController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminOrderReceiptController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MaxOrderWebhookController;
use App\Http\Controllers\TelegramOrderWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/contacts', ContactsController::class)->name('contacts');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware('throttle:orders')
    ->name('orders.store');
Route::post('/telegram/orders/webhook/{secret}', TelegramOrderWebhookController::class)
    ->name('telegram.orders.webhook');
Route::post('/max/orders/webhook/{secret}', MaxOrderWebhookController::class)
    ->name('max.orders.webhook');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/forgot-password', [PasswordResetCodeController::class, 'store'])->name('password.email');
    Route::post('/reset-password', [NewPasswordWithCodeController::class, 'store'])
        ->middleware('throttle:password-reset-verify')
        ->name('password.update');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/orders/{order}/receipt', [AdminOrderReceiptController::class, 'show'])->name('admin.orders.receipt');
    Route::get('/profile', [ProfileController::class, 'overview'])->name('profile.overview');
    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::get('/profile/orders', [ProfileController::class, 'orders'])->name('profile.orders');
    Route::get('/profile/orders/{order}', [ProfileController::class, 'showOrder'])->name('profile.orders.show');
    Route::post('/profile/orders/{order}/repeat', [ProfileController::class, 'repeatOrder'])->name('profile.orders.repeat');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/order-preferences', [ProfileController::class, 'updateOrderPreferences'])->name('profile.order-preferences.update');
    Route::post('/profile/order-preferences/preset', [ProfileController::class, 'storeOrderPreset'])->name('profile.order-preferences.store-preset');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
