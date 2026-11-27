<?php

namespace App\Filament\Erp\Resources\DeedBatches\RelationManagers;

use App\Models\DeedBatch;
use App\Models\DeedEntry;
use App\Models\ShareHolding;
use App\Services\Erp\DeedRegistry;
use App\Services\Erp\ErpRuleViolation;
use App\Support\Permissions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $title = 'Shareholders on this deed';

    public function isReadOnly(): bool
    {
        return false;
    }

    private function notify(callable $call, string $ok, Action $action): void
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
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['holding.share', 'holding.shareholder']))
            ->columns([
                TextColumn::make('holding.share.number')->label('Share')->prefix('#'),
                TextColumn::make('holding.shareholder.name')->label('Shareholder'),
                TextColumn::make('mutation_status')->label('Mutation')->badge()->formatStateUsing(fn (string $state) => ['none' => 'Not started', 'applied' => 'Applied', 'completed' => 'Completed'][$state])
                    ->color(fn (string $state) => ['none' => 'gray', 'applied' => 'warning', 'completed' => 'success'][$state]),
                TextColumn::make('mutation_applied_on')->label('Applied on')->date('d M Y')->placeholder('—'),
                TextColumn::make('mutation_completed_on')->label('Completed on')->date('d M Y')->placeholder('—'),
                TextColumn::make('mutation_reference')->label('Case / khatian ref.')->placeholder('—'),
            ])
            ->headerActions([
                Action::make('add')->label('Add shareholders')->icon('heroicon-o-user-plus')
                    ->visible(fn () => ! $this->getOwnerRecord()->isRegistered() && auth()->user()->can(Permissions::ERP_MANAGE))
                    ->schema([
                        Select::make('holdings')->label('Shareholders (active sales not on any deed)')->multiple()->required()->searchable()
                            ->options(function () {
                                /** @var DeedBatch $batch */
                                $batch = $this->getOwnerRecord();

                                return ShareHolding::query()->with(['share', 'shareholder'])->where('status', 'active')
                                    ->whereHas('share', fn ($q) => $q->where('project_id', $batch->project_id))
                                    ->whereNotIn('id', DeedEntry::query()->select('share_holding_id'))
                                    ->get()->sortBy(fn ($h) => $h->share->number)
                                    ->mapWithKeys(fn ($h) => [$h->id => '#'.$h->share->number.' · '.$h->shareholder->name])->all();
                            }),
                    ])
                    ->action(fn (Action $action, array $data) => $this->notify(fn () => app(DeedRegistry::class)->addHoldings($this->getOwnerRecord(), array_map('intval', $data['holdings']), auth()->user()), 'Added to the deed', $action)),
            ])
            ->recordActions([
                Action::make('mutation')->label('Update mutation')->icon('heroicon-o-arrow-path')
                    ->visible(fn (DeedEntry $record) => $record->batch->isRegistered() && $record->mutation_status !== 'completed' && auth()->user()->can(Permissions::ERP_MANAGE))
                    ->fillForm(fn (DeedEntry $record) => ['status' => $record->mutation_status === 'none' ? 'applied' : 'completed', 'applied_on' => $record->mutation_applied_on?->toDateString(), 'reference' => $record->mutation_reference])
                    ->schema([
                        Select::make('status')->options(['applied' => 'Applied (application filed)', 'completed' => 'Completed (khatian in buyer’s name)'])->required()->live(),
                        DatePicker::make('applied_on')->required()->maxDate(now()),
                        DatePicker::make('completed_on')->maxDate(now())->visible(fn ($get) => $get('status') === 'completed')->required(fn ($get) => $get('status') === 'completed'),
                        TextInput::make('reference')->label('Mutation case / khatian number')->maxLength(100),
                    ])
                    ->action(fn (Action $action, array $data, DeedEntry $record) => $this->notify(fn () => app(DeedRegistry::class)->updateMutation(
                        $record, $data['status'], Carbon::parse($data['applied_on']), filled($data['completed_on'] ?? null) ? Carbon::parse($data['completed_on']) : null, $data['reference'] ?? null, auth()->user()
                    ), 'Mutation updated', $action)),
                Action::make('remove')->label('Remove')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                    ->visible(fn (DeedEntry $record) => ! $record->batch->isRegistered() && auth()->user()->can(Permissions::ERP_MANAGE))
                    ->action(fn (Action $action, DeedEntry $record) => $this->notify(fn () => app(DeedRegistry::class)->removeEntry($record, auth()->user()), 'Removed from the deed', $action)),
            ]);
    }
}
