<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Sends one plain test message so SMTP credentials can be verified without touching real flows. */
class MailTest extends Command
{
    protected $signature = 'pcbd:mail-test {to : Recipient address}';

    protected $description = 'Send a test email using the configured mail settings.';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        $this->line('Mailer: '.config('mail.default').' · From: '.config('mail.from.address'));

        try {
            Mail::raw('This is a test message from Property Connect BD. If you can read it, outgoing email works.', fn ($m) => $m->to($to)->subject('Property Connect BD test email'));
        } catch (Throwable $e) {
            $this->components->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Sent. Check the inbox (and spam folder) of '.$to.'.');

        return self::SUCCESS;
    }
}
