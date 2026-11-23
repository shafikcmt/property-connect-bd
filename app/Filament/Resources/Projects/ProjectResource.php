<?php

namespace App\Filament\Resources\Projects;

use App\Enums\ProjectStatus;
use App\Enums\PublishStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\RelationManagers\InstallmentsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\MediaRelationManager;
use App\Filament\Resources\Projects\RelationManagers\UnitsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\UnitTypesRelationManager;
use App\Filament\Resources\Projects\RelationManagers\UpdatesRelationManager;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|\UnitEnum|null $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('project')->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Overview')->icon('heroicon-o-building-office')->columns(2)->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('developer_id')->label('Developer')->options(fn () => Developer::orderBy('name')->pluck('name', 'id'))->required()->searchable(),
                    Select::make('location_id')->label('Location')->searchable()->preload()
                        ->relationship('location', 'name', fn (Builder $query) => $query->with('parent.parent')->where('type', '!=', 'country')->orderBy('path'))
                        ->getOptionLabelFromRecordUsing(fn (Location $l) => $l->fullName()),
                    TextInput::make('address')->maxLength(255),
                    TextInput::make('summary')->maxLength(400)->columnSpanFull(),
                    Textarea::make('description')->rows(7)->columnSpanFull()->helperText('At least 150 characters to publish.'),
                ]),
                Tab::make('Status & construction')->icon('heroicon-o-wrench-screwdriver')->columns(3)->schema([
                    Select::make('status')->label('Project status')->options(ProjectStatus::options())->required()->default('upcoming'),
                    TextInput::make('construction_progress')->label('Construction progress (%)')->numeric()->minValue(0)->maxValue(100),
                    DatePicker::make('handover_on')->label('Handover date'),
                    TextInput::make('total_floors')->numeric()->minValue(1),
                    TextInput::make('total_units')->numeric()->minValue(1),
                    Toggle::make('available_units_verified')->label('Unit availability is verified')->helperText('When off, availability is not shown publicly.'),
                ]),
                Tab::make('Pricing')->icon('heroicon-o-banknotes')->columns(2)->schema([
                    TextInput::make('price_from')->numeric()->minValue(0)->prefix('৳'),
                    TextInput::make('price_to')->numeric()->minValue(0)->prefix('৳'),
                    Toggle::make('price_verified')->label('Price range is verified (show publicly)')->columnSpanFull(),
                    Textarea::make('payment_plan')->rows(4)->columnSpanFull(),
                ]),
                Tab::make('Land shares')->icon('heroicon-o-user-group')->columns(2)->schema([
                    Toggle::make('is_share_project')->label('This is a land-share project')->live()->columnSpanFull()
                        ->helperText('Each share is sold with a promise of one flat. Shows a share summary on the public page.'),
                    TextInput::make('total_shares')->numeric()->minValue(1)->maxValue(65000)->visible(fn ($get) => (bool) $get('is_share_project')),
                    TextInput::make('sold_shares')->numeric()->minValue(0)->lte('total_shares')->visible(fn ($get) => (bool) $get('is_share_project')),
                    TextInput::make('registered_shares')->label('Shares with registered deeds')->numeric()->minValue(0)->lte('sold_shares')->visible(fn ($get) => (bool) $get('is_share_project')),
                    TextInput::make('price_per_share')->numeric()->minValue(0)->prefix('৳')->visible(fn ($get) => (bool) $get('is_share_project'))
                        ->helperText('Shown publicly only when "Price range is verified" is on (Pricing tab).'),
                    TextInput::make('land_area_shatangsho')->label('Land area (শতাংশ / decimal)')->numeric()->minValue(0)->visible(fn ($get) => (bool) $get('is_share_project')),
                    TextInput::make('buildings_count')->label('Number of buildings')->numeric()->minValue(1)->maxValue(100)->visible(fn ($get) => (bool) $get('is_share_project')),
                    Toggle::make('shares_verified')->label('Share counts are verified (show publicly)')->columnSpanFull()->visible(fn ($get) => (bool) $get('is_share_project'))
                        ->helperText('Tick only after checking the register/deeds. Sold, remaining and registered counts are hidden otherwise.'),
                ]),
                Tab::make('Landing page')->icon('heroicon-o-window')->schema([
                    Toggle::make('branded_landing')->label('Show as a branded landing page')
                        ->helperText('Uses the developer\'s logo, name and brand colour. Needs a published developer with a logo and the developer\'s written permission. The page always says "Marketed by Property Connect BD".'),
                ]),
                Tab::make('Amenities & media')->icon('heroicon-o-sparkles')->schema([
                    CheckboxList::make('amenities')->relationship('amenities', 'name')->columns(3)->searchable(),
                    TextInput::make('video_url')->url()->maxLength(255),
                    TextInput::make('latitude')->numeric(),
                    TextInput::make('longitude')->numeric(),
                ]),
                Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                    TextInput::make('seo_title')->maxLength(255),
                    Textarea::make('seo_description')->rows(2)->maxLength(320),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['developer', 'location']))
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('developer.name')->label('Developer')->searchable(),
                TextColumn::make('location.name')->label('Location'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('publish_status')->label('Published')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
                IconColumn::make('is_verified')->label('Verified')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options(ProjectStatus::options()),
                SelectFilter::make('publish_status')->options(collect(PublishStatus::simple())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                SelectFilter::make('developer_id')->label('Developer')->options(fn () => Developer::orderBy('name')->pluck('name', 'id')),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [MediaRelationManager::class, UnitTypesRelationManager::class, UnitsRelationManager::class, InstallmentsRelationManager::class, UpdatesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
