<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LeadFollowUp;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

/** Hourly: tells each owner about follow-ups that have come due (once per follow-up). */
class CrmReminders extends Command
{
    protected $signature = 'pcbd:crm-reminders';

    protected $description = 'Send in-app reminders for follow-ups that are due.';

    public function handle(): int
    {
        $sent = 0;
        LeadFollowUp::query()->open()->whereNull('reminded_at')->where('due_at', '<=', now())->with(['lead', 'user'])->chunkById(100, function ($followUps) use (&$sent) {
            foreach ($followUps as $f) {
                if ($f->user && $f->lead && ! $f->lead->status->isTerminal()) {
                    Notification::make()->title('Follow-up due')->body(($f->note ?: 'Time to follow up').' · '.$f->lead->name)->warning()
                        ->actions([Action::make('open')->label('Open lead')->url('/admin/leads/'.$f->lead_id.'/edit')])->sendToDatabase($f->user);
                    $sent++;
                }
                $f->forceFill(['reminded_at' => now()])->save();
            }
        });
        $this->components->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
