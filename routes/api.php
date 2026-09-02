<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\LogoutController;
use App\Http\Controllers\auth\PasswordResetController;
use App\Http\Controllers\auth\RegisterController;
use App\Http\Controllers\auth\UserController;
use App\Http\Controllers\Catalogue\CartController as CatalogueCartController;
use App\Http\Controllers\connection\AccountController;
use App\Http\Controllers\tables\AnalyticsController;
use App\Http\Controllers\tables\CartController;
use App\Http\Controllers\tables\CategoryController;
use App\Http\Controllers\tables\CmiPaymentController;
use App\Http\Controllers\tables\CouponController;
use App\Http\Controllers\tables\FavoriteController;
use App\Http\Controllers\tables\NotificationController;
use App\Http\Controllers\tables\OrderController;
use App\Http\Controllers\tables\ProductController;
use App\Http\Controllers\tables\ReviewController;
use App\Http\Controllers\tables\SettingController;
use App\Http\Controllers\tables\SupportMessageController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:10,1');
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:3,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
require __DIR__.'/Catalogue.php';
Route::post('/analytics/events', [AnalyticsController::class, 'store'])->middleware('throttle:120,1');
Route::post('/delivery/quote', [OrderController::class, 'deliveryQuote'])->middleware('throttle:60,1');
Route::post('/support/messages', [SupportMessageController::class, 'store'])->middleware('throttle:10,1');
Route::match(['get', 'post'], '/payments/cmi/ok', [CmiPaymentController::class, 'ok'])->middleware('throttle:30,1');
Route::match(['get', 'post'], '/payments/cmi/fail', [CmiPaymentController::class, 'fail'])->middleware('throttle:30,1');
Route::post('/payments/cmi/callback', [CmiPaymentController::class, 'callback'])->middleware('throttle:60,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin');
    Route::prefix('user')->controller(AccountController::class)->group(function () {
        Route::get('/', 'show');
        Route::put('/', 'update');
        Route::delete('/delete', 'delete');
        Route::patch('/change-password', 'changePassword');
    });
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::middleware('role:client')->group(function () {
        Route::get('/support/messages', [SupportMessageController::class, 'myMessages']);
        Route::patch('/support/messages/{supportMessage}/close', [SupportMessageController::class, 'close']);
        Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
        Route::post('/cart/merge', [CatalogueCartController::class, 'merge']);
        Route::get('/user/favorites', [FavoriteController::class, 'index']);
        Route::post('/user/favorites/{product}', [FavoriteController::class, 'store']);
        Route::delete('/user/favorites/{product}', [FavoriteController::class, 'destroy']);
        Route::prefix('user/cart')->controller(CartController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/add', 'add');
            Route::put('/update-quantity/{cartItem}', 'updateQuantity');
            Route::delete('/remove/{cartItem}', 'remove');
            Route::delete('/clear', 'clear');
        });
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });
    Route::middleware('role:livreur')->group(function () {
        Route::get('/livreur/orders', [OrderController::class, 'livreurOrders']);
        Route::post('/livreur/orders/{order}/accept', [OrderController::class, 'acceptDelivery']);
        Route::put('/livreur/orders/{order}/status', [OrderController::class, 'livreurUpdateStatus']);
    });
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('coupons', CouponController::class)->except('show');
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::get('/orders/export/pdf', [OrderController::class, 'exportPDF']);
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt']);
        Route::get('/orders/{order}', [OrderController::class, 'adminShow']);
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/assign', [OrderController::class, 'assignLivreur']);
        Route::get('/livreurs', [OrderController::class, 'livreurs']);
        Route::get('/stats', [OrderController::class, 'stats']);
        Route::get('/sales-by-day', [OrderController::class, 'salesByDay']);
        Route::get('/low-stock', [ProductController::class, 'lowStock']);
        Route::get('/analytics', [AnalyticsController::class, 'summary']);
        Route::get('/settings', [SettingController::class, 'show']);
        Route::put('/settings/delivery', [SettingController::class, 'updateDelivery']);
        Route::get('/support/messages', [SupportMessageController::class, 'index']);
        Route::patch('/support/messages/{supportMessage}', [SupportMessageController::class, 'update']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
