<?php

namespace App\Filament\Resources\SiteVisits;

use App\Enums\SiteVisitStatus;
use App\Filament\Resources\SiteVisits\Pages\ListSiteVisits;
use App\Filament\Support\LeadActions;
use App\Filament\Support\SiteVisitActions;
use App\Models\SiteVisit;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Site-visit board: confirm, reschedule, complete, cancel or mark no-show (docs/13 §5). */
class SiteVisitResource extends Resource
{
    protected static ?string $model = SiteVisit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Inquiries';

    protected static ?string $navigationLabel = 'Site visits';

    protected static ?string $modelLabel = 'site visit';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $n = SiteVisit::visibleTo(auth()->user())->where('status', SiteVisitStatus::Requested->value)->count();

        return $n ? (string) $n : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['lead', 'property', 'project', 'assignee']))
            ->defaultSort('preferred_date')
            ->columns([
                TextColumn::make('preferred_date')->label('Asked for')->date('d M Y')->description(fn (SiteVisit $record) => ucfirst($record->preferred_window))->sortable(),
                TextColumn::make('scheduled_at')->label('Scheduled')->dateTime('d M Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('lead.name')->label('Buyer')->searchable()->weight('medium'),
                TextColumn::make('about')->label('Visiting')->state(fn (SiteVisit $record) => $record->property?->title ?? $record->project?->name ?? '—')->limit(30),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
                TextColumn::make('assignee.name')->label('Agent')->placeholder('—'),
                TextColumn::make('meeting_point')->placeholder('—')->limit(30)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SiteVisitStatus::options()),
                Filter::make('mine')->label('My visits')->query(fn (Builder $query) => $query->where('assigned_to', auth()->id())),
                Filter::make('upcoming')->label('Upcoming')->query(fn (Builder $query) => $query->where('status', SiteVisitStatus::Confirmed->value)->where('scheduled_at', '>=', now())),
                SelectFilter::make('assigned_to')->label('Agent')->options(fn () => LeadActions::staff()),
            ])
            ->recordActions([ActionGroup::make(SiteVisitActions::all())]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSiteVisits::route('/')];
    }
}
