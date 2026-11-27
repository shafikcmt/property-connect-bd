<?php

namespace App\Filament\Erp\Resources\Projects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** The instalment template. Each new sale gets a copy; existing sales are never changed by editing it. */
class PlanItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'planItems';

    protected static ?string $title = 'Instalment plan';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('label')->required()->maxLength(120)->placeholder('e.g. Booking, Construction instalment')->columnSpanFull(),
            TextInput::make('amount')->label('Amount per instalment')->numeric()->required()->minValue(1)->prefix('৳'),
            TextInput::make('count')->label('Number of instalments')->numeric()->required()->minValue(1)->maxValue(360)->default(1),
            TextInput::make('start_after_months')->label('First due (months after sale)')->numeric()->required()->minValue(0)->maxValue(600)->default(0),
            TextInput::make('interval_months')->label('Months between instalments')->numeric()->required()->minValue(1)->maxValue(60)->default(1),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            TextColumn::make('label')->weight('medium'),
            TextColumn::make('amount')->formatStateUsing(fn ($state) => $state === null ? '—' : '৳ '.number_format((float) $state, 2)),
            TextColumn::make('count')->label('×'),
            TextColumn::make('start_after_months')->label('Starts after (months)'),
            TextColumn::make('interval_months')->label('Every (months)'),
            TextColumn::make('total')->label('Total')->state(fn ($record) => number_format((float) $record->amount * $record->count, 2)),
        ])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['developer_id' => auth()->user()->developer_id])])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
