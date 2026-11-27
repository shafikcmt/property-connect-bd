<?php

namespace App\Filament\Support;

use App\Enums\SiteVisitStatus;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\LeadRuleViolation;
use App\Services\SiteVisitWorkflow;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/** Row actions for site visits. All rules live in SiteVisitWorkflow. */
class SiteVisitActions
{
    /** @return array<int, Action> */
    public static function all(): array
    {
        return [self::confirm(), self::complete(), self::noShow(), self::cancel()];
    }

    public static function confirm(): Action
    {
        return Action::make('confirm')->label(fn (SiteVisit $record) => $record->status === SiteVisitStatus::Confirmed ? 'Reschedule' : 'Confirm')->icon('heroicon-o-calendar-days')->color('primary')
            ->visible(fn (SiteVisit $record) => ! $record->status->isClosed() && auth()->user()?->can('update', $record))
            ->fillForm(fn (SiteVisit $record) => [
                'scheduled_at' => $record->scheduled_at ?? $record->preferred_date?->setTime(match ($record->preferred_window) {
                    'afternoon' => 14, 'evening' => 17, default => 10
                }, 0),
                'assigned_to' => $record->assigned_to ?? auth()->id(), 'meeting_point' => $record->meeting_point,
            ])
            ->schema([
                DateTimePicker::make('scheduled_at')->label('Visit time')->required()->seconds(false)->helperText('Buyer asked for the preferred day and time shown in the list.'),
                Select::make('assigned_to')->label('Agent')->options(fn () => LeadActions::staff())->required()->searchable(),
                TextInput::make('meeting_point')->required()->maxLength(255)->placeholder('e.g. Site gate, Purbachal 300 ft road'),
            ])
            ->action(self::guarded(fn (SiteVisit $record, array $data) => app(SiteVisitWorkflow::class)->confirm($record, Carbon::parse($data['scheduled_at']), User::findOrFail($data['assigned_to']), $data['meeting_point'], auth()->user()), 'Visit saved'));
    }

    public static function complete(): Action
    {
        return Action::make('complete')->label('Complete')->icon('heroicon-o-check-circle')->color('success')
            ->visible(fn (SiteVisit $record) => $record->status === SiteVisitStatus::Confirmed && auth()->user()?->can('update', $record))
            ->schema([Textarea::make('feedback')->label('Buyer feedback')->required()->rows(3)->maxLength(2000)])
            ->action(self::guarded(fn (SiteVisit $record, array $data) => app(SiteVisitWorkflow::class)->complete($record, $data['feedback'], auth()->user()), 'Visit completed; follow-up scheduled'));
    }

    public static function noShow(): Action
    {
        return Action::make('noShow')->label('No-show')->icon('heroicon-o-user-minus')->color('danger')->requiresConfirmation()
            ->visible(fn (SiteVisit $record) => $record->status === SiteVisitStatus::Confirmed && auth()->user()?->can('update', $record))
            ->action(self::guarded(fn (SiteVisit $record, array $data) => app(SiteVisitWorkflow::class)->markNoShow($record, auth()->user()), 'Marked as no-show; follow-up scheduled'));
    }

    public static function cancel(): Action
    {
        return Action::make('cancelVisit')->label('Cancel')->icon('heroicon-o-x-circle')->color('gray')
            ->visible(fn (SiteVisit $record) => ! $record->status->isClosed() && auth()->user()?->can('update', $record))
            ->schema([Textarea::make('reason')->required()->rows(2)->maxLength(500)])
            ->action(self::guarded(fn (SiteVisit $record, array $data) => app(SiteVisitWorkflow::class)->cancel($record, $data['reason'], auth()->user()), 'Visit cancelled'));
    }

    public static function guarded(Closure $call, string $success): Closure
    {
        return function (Action $action, SiteVisit $record, array $data) use ($call, $success): void {
            try {
                $call($record, $data);
            } catch (LeadRuleViolation $e) {
                Notification::make()->title($e->getMessage())->danger()->send();
                $action->halt();

                return;
            }
            Notification::make()->title($success)->success()->send();
        };
    }
}
