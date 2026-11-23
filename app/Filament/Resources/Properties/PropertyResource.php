<?php

namespace App\Filament\Resources\Properties;

use App\Enums\Facing;
use App\Enums\Furnishing;
use App\Enums\PropertyStatus;
use App\Enums\PublishStatus;
use App\Enums\Purpose;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\RelationManagers\MediaRelationManager;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $n = Property::where('publish_status', PublishStatus::PendingReview->value)->count();

        return $n ? (string) $n : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('property')->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Basics')->icon('heroicon-o-home')->columns(2)->schema([
                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('purpose')->options(Purpose::options())->required()->default('sale')->live(),
                    Select::make('property_type_id')->label('Property type')->options(fn () => PropertyType::active()->pluck('name', 'id'))->required()->searchable(),
                    Select::make('location_id')->label('Location')->searchable()->preload()
                        ->relationship('location', 'name', fn (Builder $query) => $query->with('parent.parent')->where('type', '!=', 'country')->orderBy('path'))
                        ->getOptionLabelFromRecordUsing(fn (Location $l) => $l->fullName()),
                    TextInput::make('address_line')->maxLength(255),
                    Select::make('developer_id')->label('Developer')->options(fn () => Developer::orderBy('name')->pluck('name', 'id'))->searchable()->live(),
                    Select::make('project_id')->label('Project')->searchable()
                        ->options(fn ($get) => Project::query()->when($get('developer_id'), fn ($q, $d) => $q->where('developer_id', $d))->orderBy('name')->pluck('name', 'id')),
                    Select::make('property_status')->label('Property status')->options(PropertyStatus::options())->required()->default('ready'),
                    DatePicker::make('handover_on')->label('Handover date'),
                    Textarea::make('description')->rows(6)->columnSpanFull()->helperText('At least 80 characters to publish. Plain text.'),
                ]),
                Tab::make('Price & specs')->icon('heroicon-o-currency-bangladeshi')->columns(3)->schema([
                    TextInput::make('price')->numeric()->minValue(0)->prefix('৳')->helperText('Total price (sale) or rent per period.'),
                    Toggle::make('price_negotiable')->label('Price negotiable'),
                    Select::make('rent_period')->options(['monthly' => 'Monthly', 'yearly' => 'Yearly'])->visible(fn ($get) => $get('purpose') === 'rent'),
                    TextInput::make('size_sqft')->label('Size (sqft)')->numeric()->minValue(1),
                    TextInput::make('bedrooms')->numeric()->minValue(0)->maxValue(50),
                    TextInput::make('bathrooms')->numeric()->minValue(0)->maxValue(50),
                    TextInput::make('balconies')->numeric()->minValue(0)->maxValue(50),
                    TextInput::make('floor_no')->label('Floor')->numeric(),
                    TextInput::make('total_floors')->numeric()->minValue(1),
                    Select::make('facing')->options(Facing::options()),
                    TextInput::make('parking_spaces')->numeric()->minValue(0),
                    Select::make('furnishing')->options(Furnishing::options()),
                    TextInput::make('year_built')->numeric()->minValue(1900)->maxValue(2100),
                    Textarea::make('payment_plan')->rows(3)->columnSpanFull(),
                ]),
                Tab::make('Amenities & nearby')->icon('heroicon-o-sparkles')->schema([
                    CheckboxList::make('amenities')->relationship('amenities', 'name')->columns(3)->searchable(),
                    TagsInput::make('nearby')->label('Nearby facilities')->placeholder('e.g. Gulshan Circle 1 — 400 m')->helperText('Only verified distances.'),
                    TextInput::make('video_url')->url()->maxLength(255)->helperText('YouTube or Vimeo link.'),
                ]),
                Tab::make('Map')->icon('heroicon-o-map-pin')->columns(2)->schema([
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                ]),
                Tab::make('Private owner contact')->icon('heroicon-o-lock-closed')->columns(2)->schema([
                    TextInput::make('owner_name')->maxLength(255),
                    TextInput::make('owner_phone')->tel()->maxLength(30),
                    TextInput::make('owner_email')->email()->maxLength(255),
                    Toggle::make('show_owner_contact')->label('Show owner contact publicly (only with the owner\'s consent)'),
                ])->visible(fn () => auth()->user()?->can('listings.manage')),
                Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                    TextInput::make('seo_title')->maxLength(255)->helperText('Defaults to a generated title.'),
                    Textarea::make('seo_description')->rows(2)->maxLength(320)->helperText('Aim for ≤ 155 characters.'),
                    DatePicker::make('expires_at')->label('Listing expires on'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['type', 'location.parent']))
            ->columns([
                TextColumn::make('public_code')->label('ID')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(45)->weight('medium'),
                TextColumn::make('purpose')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('type.name')->label('Type'),
                TextColumn::make('location.name')->label('Location')->searchable(),
                TextColumn::make('price')->money('BDT')->sortable(),
                TextColumn::make('publish_status')->label('Status')->badge()->formatStateUsing(fn ($s) => $s->label())->color(fn ($s) => $s->color()),
                IconColumn::make('is_verified')->label('Verified')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('publish_status')->options(collect(PublishStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                SelectFilter::make('purpose')->options(Purpose::options()),
                SelectFilter::make('property_type_id')->label('Type')->options(fn () => PropertyType::active()->pluck('name', 'id')),
                TernaryFilter::make('is_verified')->label('Verified'),
                TernaryFilter::make('is_featured')->label('Featured'),
                TrashedFilter::make(),
            ])
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
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'edit' => EditProperty::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
