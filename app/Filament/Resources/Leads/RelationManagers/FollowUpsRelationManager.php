<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Models\LeadFollowUp;
use App\Services\LeadRuleViolation;
use App\Services\LeadWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    protected static ?string $title = 'Follow-ups';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('due_at')->columns([
            TextColumn::make('due_at')->label('Due')->dateTime('d M Y H:i')->sortable()->color(fn (LeadFollowUp $record) => $record->isOverdue() ? 'danger' : null),
            TextColumn::make('note')->wrap()->placeholder('—'),
            TextColumn::make('user.name')->label('Owner')->placeholder('—'),
            IconColumn::make('completed_at')->label('Done')->boolean()->getStateUsing(fn (LeadFollowUp $record) => $record->completed_at !== null),
            TextColumn::make('outcome')->wrap()->placeholder('—'),
        ])->recordActions([
            Action::make('complete')->label('Complete')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn (LeadFollowUp $record) => $record->completed_at === null && auth()->user()?->can('update', $record->lead))
                ->schema([Textarea::make('outcome')->required()->rows(2)->maxLength(1000)->helperText('What happened? (required)')])
                ->action(function (Action $action, LeadFollowUp $record, array $data): void {
                    try {
                        app(LeadWorkflow::class)->completeFollowUp($record, $data['outcome'], auth()->user());
                    } catch (LeadRuleViolation $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                        $action->halt();

                        return;
                    }
                    Notification::make()->title('Follow-up completed')->success()->send();
                }),
        ]);
    }
}
