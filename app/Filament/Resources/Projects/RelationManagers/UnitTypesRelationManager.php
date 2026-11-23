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

class UnitTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'unitTypes';

    protected static ?string $title = 'Unit types';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(255)->placeholder('e.g. Type A — 3 bed')->columnSpanFull(),
            TextInput::make('bedrooms')->numeric()->minValue(0),
            TextInput::make('bathrooms')->numeric()->minValue(0),
            TextInput::make('size_sqft_min')->label('Size from (sqft)')->numeric()->minValue(1),
            TextInput::make('size_sqft_max')->label('Size to (sqft)')->numeric()->minValue(1)->gte('size_sqft_min'),
            TextInput::make('price_from')->numeric()->minValue(0)->prefix('৳'),
            TextInput::make('price_to')->numeric()->minValue(0)->prefix('৳')->gte('price_from'),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->reorderable('sort_order')->columns([
            TextColumn::make('name')->weight('medium'),
            TextColumn::make('bedrooms')->label('Beds'),
            TextColumn::make('size_sqft_min')->label('Size')->formatStateUsing(fn ($state, $record) => $state ? number_format((float) $state).($record->size_sqft_max ? '–'.number_format((float) $record->size_sqft_max) : '').' sqft' : '—'),
            TextColumn::make('price_from')->formatStateUsing(fn ($state) => $state === null ? null : Money::short($state))->placeholder('—'),
        ])->headerActions([CreateAction::make()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
