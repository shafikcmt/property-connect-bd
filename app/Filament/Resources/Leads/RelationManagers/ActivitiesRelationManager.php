<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Read-only timeline; entries are created by the lead actions only. */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Timeline';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->paginated([10, 25])->columns([
            TextColumn::make('created_at')->label('When')->dateTime('d M Y H:i')->sortable(),
            TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => $state->label()),
            TextColumn::make('body')->wrap()->label('Details'),
            TextColumn::make('user.name')->label('By')->placeholder('System'),
        ]);
    }
}
