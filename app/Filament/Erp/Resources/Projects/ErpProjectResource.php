<?php

namespace App\Filament\Erp\Resources\Projects;

use App\Filament\Erp\Resources\Projects\Pages\ListErpProjects;
use App\Filament\Erp\Resources\Projects\Pages\ViewErpProject;
use App\Filament\Erp\Resources\Projects\RelationManagers\MilestonesRelationManager;
use App\Filament\Erp\Resources\Projects\RelationManagers\PlanItemsRelationManager;
use App\Filament\Erp\Resources\Projects\RelationManagers\SharesRelationManager;
use App\Models\Project;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** The developer's own projects, read-only here: shares and the instalment plan are managed on the project page. */
class ErpProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $slug = 'projects';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Projects';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $modelLabel = 'project';

    protected static ?string $recordTitleAttribute = 'name';

    /** Projects are platform listings, so their policy is bypassed: a developer's staff may view only their own company's. */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $u = auth()->user();
        $own = $record === null || ! $record->exists || $record->developer_id === $u?->developer_id;
        $allowed = $u?->developer_id !== null && $u->can(Permissions::ERP_VIEW) && in_array($action, ['viewAny', 'view'], true) && $own;

        return $allowed ? Response::allow() : Response::deny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('developer_id', auth()->user()->developer_id);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project')->columns(4)->schema([
                TextEntry::make('name'),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextEntry::make('total_shares')->label('Shares')->placeholder('—'),
                TextEntry::make('sold_shares')->label('Sold')->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['shares', 'shares as sold_count' => fn ($q) => $q->where('status', 'sold')]))
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('shares_count')->label('Shares'),
                TextColumn::make('sold_count')->label('Sold'),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [SharesRelationManager::class, PlanItemsRelationManager::class, MilestonesRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListErpProjects::route('/'), 'view' => ViewErpProject::route('/{record}')];
    }
}
