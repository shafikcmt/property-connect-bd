<?php

namespace App\Filament\Erp\Resources\DeedBatches;

use App\Filament\Erp\Resources\DeedBatches\Pages\ListDeedBatches;
use App\Filament\Erp\Resources\DeedBatches\Pages\ViewDeedBatch;
use App\Filament\Erp\Resources\DeedBatches\RelationManagers\EntriesRelationManager;
use App\Models\DeedBatch;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Registered deeds: one deed lists many shareholders. Drafts can be edited; a registered deed is fixed and drives the public "deeds registered" figure. */
class DeedBatchResource extends Resource
{
    protected static ?string $model = DeedBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Deeds & mutation';

    protected static ?string $modelLabel = 'deed';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Deed')->columns(4)->schema([
                TextEntry::make('project.name'),
                TextEntry::make('status')->badge()->color(fn (string $state) => $state === 'registered' ? 'success' : 'warning'),
                TextEntry::make('deed_number')->placeholder('Not registered yet'),
                TextEntry::make('registry_office')->placeholder('—'),
                TextEntry::make('registered_on')->date('d M Y')->placeholder('—'),
                TextEntry::make('notes')->placeholder('—')->columnSpan(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('project')->withCount('entries'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('project.name')->label('Project'),
                TextColumn::make('deed_number')->placeholder('Draft')->searchable(),
                TextColumn::make('registry_office')->placeholder('—'),
                TextColumn::make('registered_on')->date('d M Y')->placeholder('—'),
                TextColumn::make('entries_count')->label('Shareholders'),
                TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'registered' ? 'success' : 'warning'),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [EntriesRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListDeedBatches::route('/'), 'view' => ViewDeedBatch::route('/{record}')];
    }
}
