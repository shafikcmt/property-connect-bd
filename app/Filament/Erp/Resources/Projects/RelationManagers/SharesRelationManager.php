<?php

namespace App\Filament\Erp\Resources\Projects\RelationManagers;

use App\Filament\Erp\Support\ErpActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SharesRelationManager extends RelationManager
{
    protected static string $relationship = 'shares';

    protected static ?string $title = 'Shares';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('activeHolding.shareholder'))
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')->label('Share')->prefix('#')->sortable(),
                TextColumn::make('area_shatangsho')->label('Land (decimal)')->placeholder('—'),
                TextColumn::make('list_price')->label('List price')->formatStateUsing(fn ($state) => $state === null ? '—' : '৳ '.number_format((float) $state, 2))->placeholder('—'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
                TextColumn::make('holder')->label('Shareholder')->state(fn ($record) => $record->activeHolding?->shareholder?->name ?? '—'),
            ])
            ->headerActions([ErpActions::generateShares()])
            ->recordActions([ErpActions::sellShare()]);
    }
}
