<?php

namespace App\Filament\Support;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadRuleViolation;
use App\Services\LeadWorkflow;
use App\Support\Permissions;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/** Shared lead actions for the inbox rows and the lead page. All rules live in LeadWorkflow. */
class LeadActions
{
    /** @return array<int, Action> */
    public static function all(): array
    {
        return [self::assign(), self::changeStatus(), self::logActivity(), self::scheduleFollowUp()];
    }

    public static function assign(): Action
    {
        return Action::make('assign')->label('Assign')->icon('heroicon-o-user-plus')->color('gray')
            ->visible(fn (Lead $record) => auth()->user()?->can('assign', $record))
            ->fillForm(fn (Lead $record) => ['assigned_to' => $record->assigned_to])
            ->schema([Select::make('assigned_to')->label('Assign to')->searchable()->placeholder('Unassigned')
                ->options(fn () => self::staff())])
            ->action(self::guarded(fn (Lead $record, array $data) => app(LeadWorkflow::class)->assign($record, filled($data['assigned_to'] ?? null) ? User::findOrFail($data['assigned_to']) : null, auth()->user()), 'Assignment saved'));
    }

    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')->label('Change status')->icon('heroicon-o-arrow-path')->color('gray')
            ->visible(fn (Lead $record) => auth()->user()?->can('update', $record))
            ->fillForm(fn (Lead $record) => ['status' => $record->status->value])
            ->schema([
                Select::make('status')->options(LeadStatus::options())->required()->live(),
                Textarea::make('reason')->rows(2)->helperText('Required for Lost, Not interested, Invalid, Converted, reopening and moving backwards.'),
                Select::make('duplicate_of_id')->label('Original lead')->searchable()->visible(fn ($get) => $get('status') === 'duplicate')
                    ->getSearchResultsUsing(fn (string $search) => Lead::visibleTo(auth()->user())->where('name', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%')->limit(20)->get()->mapWithKeys(fn (Lead $l) => [$l->id => "#{$l->id} {$l->name}"])->all()),
            ])
            ->action(self::guarded(fn (Lead $record, array $data) => app(LeadWorkflow::class)->changeStatus($record, LeadStatus::from($data['status']), auth()->user(), $data['reason'] ?? null, filled($data['duplicate_of_id'] ?? null) ? (int) $data['duplicate_of_id'] : null), 'Status updated'));
    }

    public static function logActivity(): Action
    {
        return Action::make('logActivity')->label('Log activity')->icon('heroicon-o-chat-bubble-left-ellipsis')->color('primary')
            ->visible(fn (Lead $record) => auth()->user()?->can('update', $record))
            ->schema([
                Select::make('type')->options(LeadActivityType::loggable())->required()->default('call'),
                Textarea::make('body')->label('What happened?')->required()->rows(3)->maxLength(2000),
            ])
            ->action(self::guarded(fn (Lead $record, array $data) => app(LeadWorkflow::class)->logActivity($record, LeadActivityType::from($data['type']), $data['body'], auth()->user()), 'Activity logged'));
    }

    public static function scheduleFollowUp(): Action
    {
        return Action::make('scheduleFollowUp')->label('Schedule follow-up')->icon('heroicon-o-clock')->color('gray')
            ->visible(fn (Lead $record) => auth()->user()?->can('update', $record) && ! $record->status->isTerminal())
            ->schema([
                DateTimePicker::make('due_at')->label('When')->required()->seconds(false)->default(now()->addDay()->setTime(10, 0)),
                Textarea::make('note')->rows(2)->maxLength(255),
            ])
            ->action(self::guarded(fn (Lead $record, array $data) => app(LeadWorkflow::class)->scheduleFollowUp($record, Carbon::parse($data['due_at']), $data['note'] ?? null, auth()->user()), 'Follow-up scheduled'));
    }

    /** Staff who can work leads (managers, agents, super admins). @return array<int, string> */
    public static function staff(): array
    {
        return User::query()->where('status', 'active')
            ->where(fn ($q) => $q->permission([Permissions::LEADS_WORK, Permissions::LEADS_MANAGE])->orWhereHas('roles', fn ($r) => $r->where('name', 'super-admin')))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    /** Runs a workflow call; a broken rule becomes a red toast and keeps the dialog open. */
    public static function guarded(Closure $call, string $success): Closure
    {
        return function (Action $action, Lead $record, array $data) use ($call, $success): void {
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
