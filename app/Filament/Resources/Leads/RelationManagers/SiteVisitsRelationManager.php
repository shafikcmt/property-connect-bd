<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Filament\Support\SiteVisitActions;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteVisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'siteVisits';

    protected static ?string $title = 'Site visits';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('preferred_date', 'desc')->columns([
            TextColumn::make('preferred_date')->label('Asked for')->date('d M Y')->description(fn ($record) => ucfirst($record->preferred_window)),
            TextColumn::make('scheduled_at')->label('Scheduled')->dateTime('d M Y H:i')->placeholder('—'),
            TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
            TextColumn::make('assignee.name')->label('Agent')->placeholder('—'),
            TextColumn::make('feedback')->wrap()->placeholder('—'),
        ])->recordActions([ActionGroup::make(SiteVisitActions::all())]);
    }
}
