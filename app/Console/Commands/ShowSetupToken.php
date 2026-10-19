<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\SetupToken;
use Illuminate\Console\Command;

class ShowSetupToken extends Command
{
    protected $signature = 'app:setup-token';

    protected $description = 'Show the token needed to create the first admin account on /setup';

    public function handle(): int
    {
        if (User::exists()) {
            $this->info('Setup is already complete. No token is needed.');

            return self::SUCCESS;
        }

        if (! SetupToken::required()) {
            $this->info('No setup token is required in this environment.');

            return self::SUCCESS;
        }

        $this->line('Setup token: <comment>'.SetupToken::value().'</comment>');
        $this->line('Enter it on '.rtrim(config('app.url'), '/').'/setup to create the first admin account.');

        return self::SUCCESS;
    }
}
