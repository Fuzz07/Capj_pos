<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard or login
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

// Login Routes (handled with custom single-tab awareness in AuthController)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('password.otp.verify');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Authenticated Routes
Route::middleware(['auth', 'single.session'])->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    // POS & Ordering
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/order', [PosController::class, 'processOrder'])->name('pos.order');
    Route::get('/pos/receipt/{order}', [PosController::class, 'receipt'])->name('pos.receipt');
    Route::post('/orders/{order}/cancel', [PosController::class, 'cancelOrder'])->name('orders.cancel');

    // Shared Inventory Access
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/{inventory}/stock', [InventoryController::class, 'addStock'])->name('inventory.add-stock');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Cashless Payments
    Route::get('/payments/gcash/{order}', [PaymentController::class, 'gcashCheckout'])->name('payments.gcash');
    Route::get('/payments/paymongo/{order}', [PaymentController::class, 'paymongoCheckout'])->name('payments.paymongo');
    Route::get('/payments/status/{order}', [PaymentController::class, 'checkStatus'])->name('payments.status');

    // Admin Only Routes
    Route::middleware('app.admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Sales & Reports (owner view)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        // Admin-Only Inventory Actions
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

        // User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/verify', [UserController::class, 'sendVerification'])->name('users.verify');

        // System Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.test-mail');
        Route::post('/settings/gcash-qr', [SettingsController::class, 'uploadGcashQr'])->name('settings.gcash-qr');
    });

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // Logs
    Route::get('/logs', function () {
        $logs = App\Models\ActivityLog::with('user')->latest()->paginate(25);
        return view('logs.index', compact('logs'));
    })->name('logs.index');
});

// PayMongo Webhook Endpoint
Route::post('/webhooks/paymongo', [PaymentController::class, 'handleWebhook'])->name('webhooks.paymongo');

// Public Email Verification Routes (OTP code entry — kept outside 'guest'
// middleware so the form still opens when another account is signed in)
Route::get('/verify-email', [AuthController::class, 'showVerifyEmail'])->name('email.verify');
Route::post('/verify-email', [AuthController::class, 'verifyEmailOtp'])->name('email.verify.submit');
