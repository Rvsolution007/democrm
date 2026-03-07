<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\Api\V1\RolesController;
use App\Http\Controllers\Api\V1\CategoriesController;
use App\Http\Controllers\Api\V1\ProductsController;
use App\Http\Controllers\Api\V1\LeadsController;
use App\Http\Controllers\Api\V1\ClientsController;
use App\Http\Controllers\Api\V1\QuotesController;
use App\Http\Controllers\Api\V1\ActivitiesController;
use App\Http\Controllers\Api\V1\TasksController;
use App\Http\Controllers\Api\V1\ReportsController;
use App\Http\Controllers\Api\V1\FacebookWebhookController;
use App\Http\Controllers\Api\V1\CompanyController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // =====================
    // Public/Auth Routes
    // =====================
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    // =====================
    // Webhooks (No auth, but verified differently)
    // =====================
    Route::prefix('webhooks')->group(function () {
        // Facebook Lead Ads Webhook
        Route::get('facebook', [FacebookWebhookController::class, 'verify']);
        Route::post('facebook', [FacebookWebhookController::class, 'handle'])
            ->middleware('throttle:120,1'); // 120 requests per minute
    });

    // =====================
    // Protected Routes
    // =====================
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('refresh', [AuthController::class, 'refresh']);
        });

        // Company Settings
        Route::prefix('company')->group(function () {
            // Route::get('/', [CompanyController::class, 'show']);
            // Route::put('/', [CompanyController::class, 'update']);
            // Route::post('logo', [CompanyController::class, 'uploadLogo']);
        });

        // Users
        Route::apiResource('users', UsersController::class);
        Route::post('users/{id}/activate', [UsersController::class, 'activate']);
        Route::post('users/{id}/deactivate', [UsersController::class, 'deactivate']);

        // Roles
        Route::get('roles/permissions', [RolesController::class, 'permissions']);
        Route::apiResource('roles', RolesController::class);
        Route::put('roles/{id}/permissions', [RolesController::class, 'setPermissions']);

        // Categories
        Route::get('categories/tree', [CategoriesController::class, 'tree']);
        Route::apiResource('categories', CategoriesController::class);

        // Products
        Route::get('products/search', [ProductsController::class, 'search']);
        Route::apiResource('products', ProductsController::class);
        Route::post('products/{id}/stock', [ProductsController::class, 'updateStock']);

        // Leads
        Route::apiResource('leads', LeadsController::class);
        Route::post('leads/{id}/assign', [LeadsController::class, 'assign']);
        Route::post('leads/{id}/follow-up', [LeadsController::class, 'setFollowUp']);
        Route::post('leads/{id}/stage', [LeadsController::class, 'updateStage']);
        Route::post('leads/{id}/convert', [LeadsController::class, 'convertToClient']);

        // Clients
        Route::apiResource('clients', ClientsController::class);

        // Quotes
        Route::get('quotes/next-number', [QuotesController::class, 'nextNumber']);
        Route::apiResource('quotes', QuotesController::class);
        Route::post('quotes/{id}/items', [QuotesController::class, 'addItem']);
        Route::delete('quotes/{quoteId}/items/{itemId}', [QuotesController::class, 'removeItem']);
        Route::post('quotes/{id}/status', [QuotesController::class, 'updateStatus']);

        // Activities
        Route::apiResource('activities', ActivitiesController::class)->except(['update']);
        Route::get('entities/{entityType}/{entityId}/activities', [ActivitiesController::class, 'forEntity']);

        // Tasks
        Route::apiResource('tasks', TasksController::class);
        Route::post('tasks/{id}/done', [TasksController::class, 'markDone']);
        Route::post('tasks/{id}/status', [TasksController::class, 'updateStatus']);
        Route::get('entities/{entityType}/{entityId}/tasks', [TasksController::class, 'forEntity']);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('dashboard', [ReportsController::class, 'dashboard']);
            Route::get('leads', [ReportsController::class, 'leads']);
            Route::get('quotes', [ReportsController::class, 'quotes']);
            Route::get('activities', [ReportsController::class, 'activities']);
        });
    });
});
