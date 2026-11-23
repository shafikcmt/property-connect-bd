<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Instalment schedule';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('label')->required()->maxLength(120)->placeholder('e.g. Land share price / Booking / 1st construction instalment')->columnSpanFull(),
            TextInput::make('timing')->maxLength(120)->placeholder('e.g. At booking, Month 6, Monthly for 24 months'),
            TextInput::make('amount')->numeric()->minValue(0)->prefix('৳')->helperText('Leave empty if the amount is not fixed.'),
            TextInput::make('note')->maxLength(255)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->reorderable('sort_order')->defaultSort('sort_order')->columns([
            TextColumn::make('label')->weight('medium'),
            TextColumn::make('timing')->placeholder('—'),
            TextColumn::make('amount')->formatStateUsing(fn ($state) => $state === null ? null : Money::short($state))->placeholder('—'),
        ])->headerActions([CreateAction::make()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
