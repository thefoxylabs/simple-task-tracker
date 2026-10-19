<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Console\Command;

/**
 * A way back in if someone loses their phone, or email stops working while
 * two-factor authentication is required.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'app:reset-two-factor
                            {email? : Reset two-factor for this person}
                            {--disable-requirement : Stop requiring two-factor for everyone}';

    protected $description = 'Reset a person\'s two-factor sign-in, or switch off the organisation-wide requirement';

    public function handle(TwoFactor $twoFactor): int
    {
        if (! $this->argument('email') && ! $this->option('disable-requirement')) {
            $this->error('Give an email address, or use --disable-requirement.');

            return self::INVALID;
        }

        if ($this->option('disable-requirement')) {
            settings()->set(['two_factor_required' => false]);
            $this->info('Two-factor authentication is no longer required for everyone.');
        }

        if ($email = $this->argument('email')) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->error("No account found for {$email}.");

                return self::FAILURE;
            }

            $twoFactor->reset($user);
            $this->info("Two-factor sign-in reset for {$user->name}. They'll use email codes if two-factor is required.");
        }

        return self::SUCCESS;
    }
}
