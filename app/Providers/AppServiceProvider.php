<?php

namespace App\Providers;

use App\Enums\Role;
use App\Http\Middleware\EnsureTwoFactorPassed;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use App\Support\Mailer;
use App\Support\Settings;
use App\Support\SystemStatus;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        $this->applySettings($this->app->make(Settings::class));

        // Track email delivery for Settings → System status.
        Event::listen(MessageSent::class, fn () => Cache::forever(SystemStatus::MAIL_SENT, now()->timestamp));
        Event::listen(JobFailed::class, function (JobFailed $event) {
            if ($event->job->resolveName() === SendQueuedNotifications::class) {
                SystemStatus::recordMailFailure($event->exception);
            }
        });

        // At least 10 characters with letters and numbers.
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        // Re-check on every Livewire request that the user hasn't been deactivated.
        Livewire::addPersistentMiddleware([EnsureUserIsActive::class, EnsureTwoFactorPassed::class]);

        // Admins and managers plan work, manage projects and assign tasks.
        Gate::define('manage', fn (User $user) => $user->canManage());

        // Only admins can change company settings and branding.
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Admins manage everyone; managers manage staff accounts only.
        Gate::define('manage-user', function (User $user, ?User $target = null) {
            if ($user->isAdmin()) {
                return true;
            }

            return $user->isManager() && ($target === null || $target->role === Role::Staff);
        });
    }

    protected function applySettings(Settings $settings): void
    {
        config([
            'app.name' => $settings->get('app_name'),
            // The page-loading bar shown while navigating between pages.
            'livewire.navigate.progress_bar_color' => $settings->get('primary_color'),
        ]);

        $timezone = $settings->get('timezone');

        if (in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        Mailer::configure($settings);
    }
}
