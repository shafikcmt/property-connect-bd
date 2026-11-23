<?php

namespace App\Filament\Resources\Developers;

use App\Filament\Resources\Developers\Pages\CreateDeveloper;
use App\Filament\Resources\Developers\Pages\EditDeveloper;
use App\Filament\Resources\Developers\Pages\ListDevelopers;
use App\Filament\Resources\Developers\RelationManagers\MediaRelationManager;
use App\Models\Developer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeveloperResource extends Resource
{
    protected static ?string $model = Developer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('established_year')->numeric()->minValue(1800)->maxValue(2100)->helperText('Shown publicly only when the developer is verified.'),
                TextInput::make('summary')->maxLength(300)->columnSpanFull(),
                Textarea::make('about')->rows(6)->columnSpanFull()->helperText('At least 50 characters to publish.'),
            ]),
            Section::make('Contact')->columns(2)->schema([
                TextInput::make('website')->url()->maxLength(255),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(30),
                TextInput::make('address')->maxLength(255),
                Toggle::make('show_contact_publicly')->label('Show phone/email on the public profile')->columnSpanFull(),
            ]),
            Section::make('SEO')->collapsed()->schema([
                TextInput::make('seo_title')->maxLength(255),
                Textarea::make('seo_description')->rows(2)->maxLength(320),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('projects'))
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('projects_count')->label('Projects')->sortable(),
                TextColumn::make('publish_status')->label('Status')->badge()->formatStateUsing(fn ($s) => $s->label())->color(fn ($s) => $s->color()),
                IconColumn::make('is_verified')->label('Verified')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->defaultSort('name')
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [MediaRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevelopers::route('/'),
            'create' => CreateDeveloper::route('/create'),
            'edit' => EditDeveloper::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
