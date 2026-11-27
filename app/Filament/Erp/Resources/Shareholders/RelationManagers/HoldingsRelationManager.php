<?php

namespace App\Filament\Erp\Resources\Shareholders\RelationManagers;

use App\Models\ShareHolding;
use App\Services\Erp\HoldingLedger;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HoldingsRelationManager extends RelationManager
{
    protected static string $relationship = 'holdings';

    protected static ?string $title = 'Shares held';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['share.project', 'payments', 'instalments']))
            ->defaultSort('sold_on', 'desc')
            ->columns([
                TextColumn::make('share.number')->label('Share')->prefix('#'),
                TextColumn::make('share.project.name')->label('Project'),
                TextColumn::make('price')->formatStateUsing(fn ($state) => $state === null ? '—' : '৳ '.number_format((float) $state, 2)),
                TextColumn::make('sold_on')->date('d M Y'),
                TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('paid')->label('Paid')->state(fn (ShareHolding $record) => number_format((new HoldingLedger($record))->paid(), 2)),
                TextColumn::make('balance')->label('Balance')->state(fn (ShareHolding $record) => number_format((new HoldingLedger($record))->balance(), 2)),
            ]);
    }
}
