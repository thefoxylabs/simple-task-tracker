<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\LogoutController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

Route::get('/branding/logo', [FileController::class, 'logo'])->name('branding.logo');

Route::middleware('installed')->group(function () {
    Route::livewire('/setup', Livewire\Auth\Setup::class)->name('setup');

    Route::middleware('guest')->group(function () {
        Route::livewire('/login', Livewire\Auth\Login::class)->name('login');
        Route::livewire('/forgot-password', Livewire\Auth\ForgotPassword::class)->name('password.request');
        Route::livewire('/reset-password/{token}', Livewire\Auth\ResetPassword::class)->name('password.reset');
    });

    Route::middleware(['auth', 'auth.session', 'active'])->group(function () {
        Route::post('/logout', LogoutController::class)->name('logout');

        Route::livewire('/', Livewire\Dashboard::class)->name('dashboard');
        Route::livewire('/board', Livewire\Board::class)->name('board');
        Route::livewire('/tasks/{task}', Livewire\Tasks\Show::class)->name('tasks.show');
        Route::get('/attachments/{attachment}', [FileController::class, 'attachment'])->name('attachments.download');

        Route::livewire('/projects', Livewire\Projects\Index::class)->name('projects.index');
        Route::livewire('/projects/{project}', Livewire\Board::class)->name('projects.show');

        Route::livewire('/plans', Livewire\Plans\Index::class)->name('plans.index');
        Route::livewire('/plans/{plan}', Livewire\Plans\Show::class)->name('plans.show');

        Route::livewire('/staff/{user}', Livewire\Staff\Show::class)->name('staff.show');
        Route::livewire('/profile', Livewire\Profile::class)->name('profile');

        Route::middleware('can:manage')->group(function () {
            Route::livewire('/recurring', Livewire\RecurringTasks::class)->name('recurring.index');
            Route::livewire('/staff', Livewire\Staff\Index::class)->name('staff.index');
            Route::livewire('/groups', Livewire\StaffGroups::class)->name('groups.index');
        });

        Route::livewire('/settings', Livewire\Settings::class)->middleware('can:admin')->name('settings');
    });
});
