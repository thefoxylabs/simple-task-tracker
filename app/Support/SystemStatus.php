<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Throwable;

/**
 * Health checks for everything Task Tracker needs to work, shown on
 * Settings → System status.
 *
 * Each check is: ['title', 'status' => ok|warning|error|info, 'message', 'fix'?, 'link'?].
 */
class SystemStatus
{
    public const HEARTBEAT = 'system.scheduler_heartbeat';

    public const MAIL_SENT = 'system.mail_last_sent';

    public const MAIL_FAILED = 'system.mail_last_failure';

    /** Record a failed email so it shows on the status page. */
    public static function recordMailFailure(Throwable $e): void
    {
        Cache::forever(self::MAIL_FAILED, ['at' => now()->timestamp, 'message' => Str::limit($e->getMessage(), 300)]);
    }

    /**
     * All checks, grouped. Live checks (connecting to the mail server and
     * writing to storage) are slower and only run when $live is true.
     *
     * @return array<string, array<string, array>>
     */
    public function checks(bool $live = false): array
    {
        return [
            'Essentials' => [
                'database' => $this->database(),
                'app_key' => $this->appKey(),
                'folders' => $this->folders(),
                'php' => $this->php(),
                'assets' => $this->assets(),
                'cache' => $this->cache(),
            ],
            'Email & background jobs' => [
                'mail_config' => $this->mailConfig(),
                'mail_connection' => $live ? $this->mailConnection() : $this->pending('Mail server connection', 'Connecting to your mail server…'),
                'scheduler' => $this->scheduler(),
                'queue' => $this->queue(),
            ],
            'File storage' => [
                'storage' => $live ? $this->storage() : $this->pending('File uploads', 'Writing a test file…'),
            ],
            'Security' => [
                'https' => $this->https(),
                'debug' => $this->debug(),
                'two_factor' => $this->twoFactor(),
                'setup_token' => $this->setupToken(),
            ],
        ];
    }

    /** Problems among the quick checks, for the admin dashboard banner (cached briefly). */
    public function problems(): int
    {
        return Cache::remember('system.problem_count', now()->addMinutes(5), function () {
            return collect($this->checks())->flatten(1)->where('status', 'error')->count();
        });
    }

    // ----- Essentials -------------------------------------------------------------------

    protected function database(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            $migrator = app('migrator');
            $files = array_keys($migrator->getMigrationFiles([database_path('migrations')]));
            $pending = $migrator->repositoryExists() ? array_diff($files, $migrator->getRepository()->getRan()) : $files;

            if ($pending) {
                return $this->check('Database', 'error', count($pending).' database update(s) haven\'t been applied yet.', 'Run `php artisan migrate --force` on the server.');
            }

            return $this->check('Database', 'ok', 'Connected to '.$this->driverName($driver).' '.$version.'. All updates applied.');
        } catch (Throwable $e) {
            return $this->check('Database', 'error', 'Can\'t connect: '.Str::limit($e->getMessage(), 160), 'Check the DB_* settings in your .env file.');
        }
    }

    protected function appKey(): array
    {
        return filled(config('app.key'))
            ? $this->check('Encryption key', 'ok', 'APP_KEY is set, so sessions and saved passwords are encrypted.')
            : $this->check('Encryption key', 'error', 'APP_KEY is missing.', 'Run `php artisan key:generate`.');
    }

    protected function folders(): array
    {
        $paths = ['storage/app', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'];
        $notWritable = array_filter($paths, fn ($path) => ! is_writable(base_path($path)));

        return $notWritable
            ? $this->check('Folder permissions', 'error', 'The app can\'t write to: '.implode(', ', $notWritable).'.', 'Give the web server user write access to these folders.')
            : $this->check('Folder permissions', 'ok', 'Storage and cache folders are writable.');
    }

    protected function php(): array
    {
        $required = ['ctype', 'fileinfo', 'intl', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'];
        $missing = array_values(array_filter($required, fn ($ext) => ! extension_loaded($ext)));

        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            return $this->check('PHP', 'error', 'PHP '.PHP_VERSION.' is too old.', 'Upgrade to PHP 8.3 or newer.');
        }

        return $missing
            ? $this->check('PHP', 'error', 'Missing PHP extensions: '.implode(', ', $missing).'.', 'Install them for PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.')
            : $this->check('PHP', 'ok', 'PHP '.PHP_VERSION.' with all required extensions.');
    }

    protected function assets(): array
    {
        return file_exists(public_path('build/manifest.json'))
            ? $this->check('Styles and scripts', 'ok', 'Front-end assets are built.')
            : $this->check('Styles and scripts', 'error', 'The CSS and JavaScript haven\'t been built.', 'Run `npm ci && npm run build`.');
    }

    protected function cache(): array
    {
        try {
            $key = 'system.cache_check';
            Cache::put($key, 'ok', 10);
            $works = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $works
                ? $this->check('Cache', 'ok', 'Working ('.config('cache.default').').')
                : $this->check('Cache', 'error', 'Values saved to the cache can\'t be read back.', 'Check CACHE_STORE in .env.');
        } catch (Throwable $e) {
            return $this->check('Cache', 'error', Str::limit($e->getMessage(), 160), 'Check CACHE_STORE in .env.');
        }
    }

    // ----- Email & background jobs ------------------------------------------------------

    protected function mailConfig(): array
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');
        $source = settings('mail_enabled') ? 'Settings' : '.env';
        $link = ['Email settings', route('settings', ['tab' => 'mail'])];

        if (in_array($mailer, ['log', 'array'], true)) {
            return $this->check('Email settings', app()->environment('production') ? 'error' : 'warning',
                'Emails are only written to the log file, not delivered (mailer: '.$mailer.').',
                'Enter your SMTP details in Settings → Email (SMTP), or set MAIL_* in .env.', $link);
        }

        if (blank($from) || Str::endsWith($from, '@example.com')) {
            return $this->check('Email settings', 'warning', 'The "from" address isn\'t set to your own domain ('.($from ?: 'empty').').', 'Set a from address such as tasks@yourcompany.com.', $link);
        }

        $sent = Cache::get(self::MAIL_SENT);
        $failed = Cache::get(self::MAIL_FAILED);
        $last = $sent ? ' Last email sent '.now()->createFromTimestamp($sent)->diffForHumans().'.' : '';

        if ($failed && (! $sent || $failed['at'] > $sent)) {
            return $this->check('Email settings', 'error', 'The last email failed '.now()->createFromTimestamp($failed['at'])->diffForHumans().': '.$failed['message'], 'Check your SMTP details and send a test email.', $link);
        }

        return $this->check('Email settings', 'ok', 'Sending as '.$from.' via '.$mailer.' (from '.$source.').'.$last, null, $link);
    }

    protected function mailConnection(): array
    {
        $title = 'Mail server connection';

        if (config('mail.default') !== 'smtp') {
            return $this->check($title, 'info', 'Not using SMTP (mailer: '.config('mail.default').'), so there\'s no server to test.');
        }

        try {
            $transport = Mail::mailer('smtp')->getSymfonyTransport();

            if ($transport instanceof EsmtpTransport) {
                $stream = $transport->getStream();
                if ($stream instanceof SocketStream) {
                    $stream->setTimeout(8);
                }
                $transport->start();
                $transport->stop();
            }

            return $this->check($title, 'ok', 'Connected and signed in to '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').'.');
        } catch (Throwable $e) {
            return $this->check($title, 'error', 'Couldn\'t connect to '.config('mail.mailers.smtp.host').': '.Str::limit($e->getMessage(), 200),
                'Check the host, port, encryption, username and password.', ['Email settings', route('settings', ['tab' => 'mail'])]);
        }
    }

    protected function scheduler(): array
    {
        $beat = Cache::get(self::HEARTBEAT);
        $fix = 'Add this cron entry on the server: `* * * * * cd '.base_path().' && php artisan schedule:run`';

        if (! $beat) {
            return $this->check('Scheduler', 'error', 'The scheduler has never run, so reminders, recurring tasks and queued emails won\'t go out.', $fix);
        }

        $ago = now()->timestamp - $beat;

        return $ago > 180
            ? $this->check('Scheduler', 'error', 'The scheduler last ran '.now()->createFromTimestamp($beat)->diffForHumans().'. It should run every minute.', $fix)
            : $this->check('Scheduler', 'ok', 'Running every minute (last run '.now()->createFromTimestamp($beat)->diffForHumans().').');
    }

    protected function queue(): array
    {
        $connection = config('queue.default');

        if ($connection === 'sync') {
            return $this->check('Email queue', 'warning', 'Emails are sent while people wait for the page (QUEUE_CONNECTION=sync).', 'Set QUEUE_CONNECTION=database in .env so emails are sent in the background.');
        }

        if ($connection !== 'database') {
            return $this->check('Email queue', 'info', 'Using the "'.$connection.'" queue. Make sure a worker is running.');
        }

        try {
            $pending = DB::table(config('queue.connections.database.table', 'jobs'))->count();
            $oldest = DB::table(config('queue.connections.database.table', 'jobs'))->min('created_at');
            $failed = DB::table(config('queue.failed.table', 'failed_jobs'))->where('failed_at', '>=', now()->subDays(7))->count();
        } catch (Throwable $e) {
            return $this->check('Email queue', 'error', Str::limit($e->getMessage(), 160), 'Run `php artisan migrate --force`.');
        }

        if ($oldest && now()->timestamp - (int) $oldest > 600) {
            return $this->check('Email queue', 'error', $pending.' email(s) have been waiting more than 10 minutes. The queue isn\'t being processed.', 'Make sure the scheduler cron entry is set up (or a queue worker is running).');
        }

        if ($failed) {
            return $this->check('Email queue', 'warning', $failed.' email(s) failed to send in the last 7 days.', 'Fix the email settings, then run `php artisan queue:retry all` to send them again.');
        }

        return $this->check('Email queue', 'ok', $pending ? $pending.' email(s) waiting to be sent.' : 'Nothing waiting. No failed emails in the last 7 days.');
    }

    // ----- Storage ----------------------------------------------------------------------

    protected function storage(): array
    {
        $name = Uploads::disk();
        $label = match (config("filesystems.disks.{$name}.driver")) {
            's3' => $name === 'r2' ? 'Cloudflare R2' : 'S3 storage',
            'local' => 'the server\'s disk',
            default => $name,
        };

        try {
            $disk = Storage::build(array_merge(config("filesystems.disks.{$name}"), ['throw' => true]));
            $path = 'health-checks/'.Str::uuid().'.txt';

            $disk->put($path, 'ok');
            $readable = $disk->get($path) === 'ok';
            $disk->delete($path);

            return $readable
                ? $this->check('File uploads', 'ok', 'Uploads are saved to '.$label.' ('.$name.'). A test file was written, read and deleted.')
                : $this->check('File uploads', 'error', 'A test file was written to '.$label.' but couldn\'t be read back.', 'Check your storage settings in .env.');
        } catch (Throwable $e) {
            return $this->check('File uploads', 'error', 'Can\'t save files to '.$label.': '.Str::limit($e->getMessage(), 200),
                $name === 'r2' ? 'Check R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET and R2_ENDPOINT in .env.' : 'Check FILESYSTEM_DISK and its settings in .env.');
        }
    }

    // ----- Security ---------------------------------------------------------------------

    protected function https(): array
    {
        $url = (string) config('app.url');
        $request = request();
        $host = parse_url($url, PHP_URL_HOST);

        if ($request->getHost() && $host && strcasecmp($request->getHost(), $host) !== 0) {
            return $this->check('Address & HTTPS', 'warning', 'APP_URL is '.$url.' but you\'re visiting '.$request->getSchemeAndHttpHost().'. Links in emails will use APP_URL.', 'Set APP_URL in .env to your real address.');
        }

        if (! Str::startsWith($url, 'https://')) {
            return $this->check('Address & HTTPS', app()->environment('production') ? 'error' : 'warning', 'The app isn\'t served over HTTPS ('.$url.').', 'Set up an SSL certificate and use an https:// APP_URL.');
        }

        if (! config('session.secure')) {
            return $this->check('Address & HTTPS', 'warning', 'Served over HTTPS, but cookies aren\'t marked HTTPS-only.', 'Set SESSION_SECURE_COOKIE=true in .env.');
        }

        return $this->check('Address & HTTPS', 'ok', 'Served securely at '.$url.'.');
    }

    protected function debug(): array
    {
        if (config('app.debug') && app()->environment('production')) {
            return $this->check('Debug mode', 'error', 'Debug mode is on in production, which can show sensitive details in error pages.', 'Set APP_DEBUG=false in .env.');
        }

        return config('app.debug')
            ? $this->check('Debug mode', 'info', 'Debug mode is on ('.app()->environment().' environment). Turn it off before going live.')
            : $this->check('Debug mode', 'ok', 'Debug mode is off.');
    }

    protected function twoFactor(): array
    {
        $link = ['Security settings', route('settings', ['tab' => 'security'])];

        if (settings('two_factor_required')) {
            return $this->check('Two-factor authentication', 'ok', 'Required for everyone.', null, $link);
        }

        $without = User::active()->get()->reject->hasTwoFactor()->count();

        return $this->check('Two-factor authentication', 'warning', 'Optional. '.$without.' active '.Str::plural('person', $without).' sign in with a password only.', 'Turn on "Require two-factor authentication for everyone".', $link);
    }

    protected function setupToken(): array
    {
        return file_exists(storage_path('app/private/setup-token')) && User::exists()
            ? $this->check('Setup token', 'warning', 'A leftover setup token file is still on the server.', 'Delete storage/app/private/setup-token.')
            : $this->check('Setup wizard', 'ok', 'Setup is complete and locked.');
    }

    // ----- Helpers ----------------------------------------------------------------------

    protected function check(string $title, string $status, string $message, ?string $fix = null, ?array $link = null): array
    {
        return compact('title', 'status', 'message', 'fix', 'link');
    }

    protected function pending(string $title, string $message): array
    {
        return $this->check($title, 'pending', $message);
    }

    protected function driverName(string $driver): string
    {
        return ['mysql' => 'MySQL', 'mariadb' => 'MariaDB', 'sqlite' => 'SQLite', 'pgsql' => 'PostgreSQL'][$driver] ?? $driver;
    }
}
