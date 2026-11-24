<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep guest-facing caches fresh (sitemap is cached for an hour; this guarantees it never lingers).
Schedule::call(fn () => cache()->forget('pcbd.sitemap'))->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('pcbd:crm-reminders')->hourly()->withoutOverlapping();
