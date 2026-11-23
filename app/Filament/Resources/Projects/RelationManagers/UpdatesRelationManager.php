<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';

    protected static ?string $title = 'Project updates (timeline)';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->required()->maxLength(160)->columnSpanFull()->placeholder('e.g. Second deed batch completed — 30 shareholders'),
            DatePicker::make('happened_on')->required()->default(now()),
            Toggle::make('is_published')->label('Show on the public page')->default(true)->inline(false),
            Textarea::make('body')->rows(3)->maxLength(1000)->columnSpanFull()->helperText('Never include names, ID numbers or addresses of buyers.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('happened_on', 'desc')->columns([
            TextColumn::make('happened_on')->date('d M Y')->label('Date'),
            TextColumn::make('title')->weight('medium')->limit(70),
            IconColumn::make('is_published')->boolean()->label('Public'),
        ])->headerActions([CreateAction::make()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
