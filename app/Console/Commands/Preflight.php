<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Developer;
use App\Models\Media;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Production-readiness checks (docs/25). Exits non-zero when a blocking problem is found. */
class Preflight extends Command
{
    protected $signature = 'pcbd:preflight';

    protected $description = 'Verify the environment is safe and ready for production.';

    public function handle(): int
    {
        $isProd = app()->environment('production');
        $fail = [];
        $warn = [];

        $check = function (bool $ok, string $msg, bool $blocking = true) use (&$fail, &$warn) {
            if ($ok) {
                $this->components->twoColumnDetail($msg, '<fg=green>OK</>');
            } else {
                $this->components->twoColumnDetail($msg, $blocking ? '<fg=red>FAIL</>' : '<fg=yellow>WARN</>');
                $blocking ? $fail[] = $msg : $warn[] = $msg;
            }
        };

        $check($isProd, 'APP_ENV is production');
        $check(! config('app.debug'), 'APP_DEBUG is false');
        $check(filled(config('app.key')), 'APP_KEY is set');
        $check(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL uses https');
        $check(config('database.default') === 'pgsql', 'Database is PostgreSQL');
        $check((bool) config('pcbd.admin_mfa_required'), 'Admin 2FA enforcement is enabled');
        $check(config('session.secure') === true || ! $isProd, 'Session cookie is secure-only', $isProd);
        $check(! config('pcbd.noindex'), 'Search engines are allowed (PCBD_NOINDEX is off)', false);
        $check(! in_array(config('mail.default'), ['log', 'array'], true), 'Mail driver sends real email', false);
        $check(config('queue.default') !== 'sync', 'Queue is not sync', false);

        try {
            DB::select('select 1');
            $check(true, 'Database connection works');
            $check(User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->exists(), 'A Super Admin exists (php artisan pcbd:create-super-admin)');
            $preview = (bool) config('pcbd.allow_demo');
            $check(! Media::where('is_demo', true)->exists(), 'No demo media present (php artisan pcbd:demo-clear)', $preview ? false : true);
            $check(! Developer::where('name', 'like', 'Demo %')->exists(), 'No demo developers present (php artisan pcbd:demo-clear)', $preview ? false : true);
            $check(! $preview, 'Demo preview mode is off (PCBD_ALLOW_DEMO)', false);
        } catch (\Throwable) {
            $check(false, 'Database connection works');
        }

        $check(is_link(public_path('storage')) || is_dir(public_path('storage')), 'public/storage link exists');
        $check(is_writable(storage_path('logs')), 'storage/logs is writable');
        $check(file_exists(public_path('build/manifest.json')), 'Frontend assets are built');
        $check(filled(app(SettingsService::class)->get('contact.phone')) || filled(app(SettingsService::class)->get('contact.whatsapp')), 'Contact phone/WhatsApp set in admin Settings', false);

        $this->newLine();
        if ($fail) {
            $this->components->error(count($fail).' blocking problem(s). Fix them before launch.');

            return self::FAILURE;
        }
        $this->components->info($warn ? 'Ready, with '.count($warn).' warning(s).' : 'All checks passed.');

        return self::SUCCESS;
    }
}
