<?php

namespace App\Filament\Erp\Resources\Shareholders;

use App\Filament\Erp\Resources\Shareholders\Pages\CreateShareholder;
use App\Filament\Erp\Resources\Shareholders\Pages\EditShareholder;
use App\Filament\Erp\Resources\Shareholders\Pages\ListShareholders;
use App\Filament\Erp\Resources\Shareholders\Pages\ViewShareholder;
use App\Models\Shareholder;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Buyers of shares. Contact and ID data are private; the NID is stored encrypted and only its last four digits are ever shown. */
class ShareholderResource extends Resource
{
    protected static ?string $model = Shareholder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shareholder')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(150),
                TextInput::make('father_name')->label('Father’s name')->maxLength(150),
                TextInput::make('phone')->tel()->maxLength(20)->regex('/^\+?[0-9\s\-]{7,20}$/'),
                TextInput::make('email')->email()->maxLength(190),
                Textarea::make('address')->rows(2)->columnSpanFull()->maxLength(500),
                TextInput::make('nid')->label('National ID')->password()->revealable()->maxLength(30)->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Stored encrypted and never shown in full. Leave blank to keep the current one.' : 'Stored encrypted and never shown in full.'),
                Textarea::make('notes')->rows(2)->maxLength(1000)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shareholder')->columns(3)->schema([
                TextEntry::make('name'), TextEntry::make('father_name')->label('Father’s name')->placeholder('—'),
                TextEntry::make('phone')->placeholder('—'), TextEntry::make('email')->placeholder('—'),
                TextEntry::make('nid_masked')->label('National ID')->state(fn (Shareholder $record) => $record->maskedNid()),
                TextEntry::make('address')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['holdings as active_count' => fn ($q) => $q->where('status', 'active')]))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('phone')->searchable()->placeholder('—'),
                TextColumn::make('nid_masked')->label('National ID')->state(fn (Shareholder $record) => $record->maskedNid()),
                TextColumn::make('active_count')->label('Shares held'),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\HoldingsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShareholders::route('/'), 'create' => CreateShareholder::route('/create'),
            'view' => ViewShareholder::route('/{record}'), 'edit' => EditShareholder::route('/{record}/edit'),
        ];
    }
}
