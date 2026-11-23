<?php

namespace App\Filament\Resources\Locations;

use App\Enums\LocationType;
use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('parent_id')->label('Parent location')->searchable()->preload()
                ->relationship('parent', 'name', fn (Builder $query) => $query->with('parent.parent')->orderBy('path'))
                ->getOptionLabelFromRecordUsing(fn (Location $l) => $l->fullName()),
            Select::make('type')->options(LocationType::options())->required(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('name_bn')->label('Name (Bangla)')->maxLength(255),
            TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
            TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
            Textarea::make('description')->rows(6)->columnSpanFull()->helperText('Unique, useful area description. Public location pages are only indexed when this is filled in.'),
            TextInput::make('seo_title')->maxLength(255),
            Textarea::make('seo_description')->rows(2)->maxLength(320),
            Toggle::make('is_active')->default(true),
            Toggle::make('is_featured')->label('Featured / popular location'),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('path')->label('Path')->searchable()->sortable()->weight('medium'),
            TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => $state->label()),
            IconColumn::make('is_active')->boolean(),
            IconColumn::make('is_featured')->boolean(),

        ])->recordActions([EditAction::make()])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
