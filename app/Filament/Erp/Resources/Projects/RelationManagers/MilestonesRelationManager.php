<?php

namespace App\Filament\Erp\Resources\Projects\RelationManagers;

use App\Models\ConstructionMilestone;
use App\Services\Erp\ConstructionLog;
use App\Services\Erp\ErpRuleViolation;
use App\Support\Permissions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Construction progress log. The latest entry sets the progress bar on the public page. */
class MilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    protected static ?string $title = 'Construction progress';

    public function isReadOnly(): bool
    {
        return false;
    }

    private function run(callable $call, string $ok, Action $action): void
    {
        try {
            $call();
        } catch (ErpRuleViolation $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $action->halt();

            return;
        }
        Notification::make()->title($ok)->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('happened_on')->label('Date')->date('d M Y')->sortable(),
            TextColumn::make('title')->weight('medium'),
            TextColumn::make('progress')->label('Progress')->suffix('%'),
            IconColumn::make('public')->label('On public page')->boolean()->state(fn (ConstructionMilestone $r) => $r->project_update_id !== null),
            TextColumn::make('note')->limit(60)->placeholder('—'),
        ])->headerActions([
            Action::make('add')->label('Add milestone')->icon('heroicon-o-plus')
                ->visible(fn () => auth()->user()->can(Permissions::ERP_MANAGE))
                ->schema([
                    TextInput::make('title')->required()->maxLength(160)->placeholder('e.g. 5th floor roof cast'),
                    TextInput::make('progress')->label('Overall progress now (%)')->numeric()->required()->minValue(0)->maxValue(100),
                    DatePicker::make('happened_on')->required()->default(now())->maxDate(now()),
                    Textarea::make('note')->rows(2)->maxLength(1000),
                    Toggle::make('publish')->label('Show on the public project timeline')->default(true),
                ])
                ->action(fn (Action $action, array $data) => $this->run(fn () => app(ConstructionLog::class)->add($this->getOwnerRecord(), $data['title'], (int) $data['progress'], Carbon::parse($data['happened_on']), $data['note'] ?? null, (bool) ($data['publish'] ?? false), auth()->user()), 'Milestone added', $action)),
        ])->recordActions([
            Action::make('retract')->label('Remove')->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                ->modalDescription('Use this for a wrong entry. It also removes it from the public timeline.')
                ->visible(fn () => auth()->user()->can(Permissions::ERP_MANAGE))
                ->action(fn (Action $action, ConstructionMilestone $record) => $this->run(fn () => app(ConstructionLog::class)->retract($record, auth()->user()), 'Milestone removed', $action)),
        ]);
    }
}
