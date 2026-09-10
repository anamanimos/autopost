<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MetaIntegrationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication & SSO Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Single Sign-On (SSO) OpenID Connect ERP Damai Jaya
    Route::get('/auth/sso/redirect', [SSOController::class, 'redirect'])->name('sso.redirect');
    Route::get('/auth/sso/callback', [SSOController::class, 'callback'])->name('sso.callback');
});

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User Profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Dashboard Home Redirect
    Route::get('/', function () {
        return redirect()->route('projects.index');
    });

    // Projects Management Routes
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{id}/toggle-status', [ProjectController::class, 'toggleStatus'])->name('projects.toggleStatus');
    Route::post('projects/{id}/add-media', [ProjectController::class, 'addMedia'])->name('projects.addMedia');
    Route::post('projects/{id}/schedules', [ProjectController::class, 'addSchedule'])->name('projects.addSchedule');
    Route::post('projects/{id}/schedules/sync', [ProjectController::class, 'syncBuffer'])->name('projects.syncBuffer');
    Route::match(['get', 'post'], 'projects/{id}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');

    // Schedules & Monitoring Routes
    Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::delete('schedules/{id}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::post('schedules/{id}/run', [ScheduleController::class, 'runSingle'])->name('schedules.runSingle');
    Route::post('schedules/{id}/status', [ScheduleController::class, 'updateStatus'])->name('schedules.updateStatus');
    Route::post('schedules/publish-now', [ScheduleController::class, 'publishNow'])->name('schedules.publishNow');
    Route::post('schedules/retry-failed', [ScheduleController::class, 'retryFailed'])->name('schedules.retryFailed');
    Route::get('schedules/{id}/logs', [ScheduleController::class, 'showLogs'])->name('schedules.showLogs');

    // Meta API Integration Settings Routes
    Route::prefix('meta-integration')->name('meta.')->group(function () {
        Route::get('/', [MetaIntegrationController::class, 'index'])->name('index');
        Route::post('/credentials', [MetaIntegrationController::class, 'updateCredentials'])->name('updateCredentials');
        Route::post('/token', [MetaIntegrationController::class, 'saveManualToken'])->name('saveManualToken');
        Route::get('/oauth', [MetaIntegrationController::class, 'redirectToOAuth'])->name('oauth');
        Route::get('/callback', [MetaIntegrationController::class, 'handleOAuthCallback'])->name('callback');
        Route::post('/refresh-token', [MetaIntegrationController::class, 'refreshToken'])->name('refreshToken');
        Route::post('/test-connection', [MetaIntegrationController::class, 'testConnection'])->name('testConnection');
        Route::post('/sync-now', [MetaIntegrationController::class, 'syncNow'])->name('syncNow');
        Route::delete('/accounts/{id}', [MetaIntegrationController::class, 'deleteAccount'])->name('deleteAccount');
    });

    // Administrator Only Routes: User Management
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::post('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');

        // System Settings & Cloudflare R2 Monitoring
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings/test-r2', [SettingController::class, 'testR2'])->name('settings.testR2');
    });
});