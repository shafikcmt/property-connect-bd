<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\RequestType;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Leads\RelationManagers\FollowUpsRelationManager;
use App\Filament\Support\LeadActions;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Inquiry inbox and lead workspace (Phase 8): assignment, priority, follow-ups, timeline and SLA flags. */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Inquiries';

    protected static ?string $navigationLabel = 'Inquiries';

    protected static ?string $modelLabel = 'inquiry';

    public static function getNavigationBadge(): ?string
    {
        $n = Lead::visibleTo(auth()->user())->where('status', 'new')->count();

        return $n ? (string) $n : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact (private)')->columns(2)->schema([
                TextInput::make('name')->disabled(), TextInput::make('phone')->disabled(), TextInput::make('email')->disabled(),
                TextInput::make('request_type')->disabled(),
                Textarea::make('message')->disabled()->rows(4)->columnSpanFull(),
            ]),
            Section::make('Handling')->columns(2)->schema([
                TextInput::make('status')->disabled()->helperText('Change it with the “Change status” button.'),
                TextInput::make('assignee')->label('Assigned to')->disabled(),
                Select::make('priority')->options(LeadPriority::options())->required(),
                Textarea::make('internal_notes')->rows(4)->columnSpanFull()->helperText('Internal only. Never shown publicly.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['property', 'project', 'assignee']))
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('phone')->searchable()->copyable(),
                TextColumn::make('request_type')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('subject')->label('About')->state(fn (Lead $l) => $l->property?->title ?? $l->project?->name ?? '—')->limit(30),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state === LeadStatus::New ? 'warning' : 'gray'),
                TextColumn::make('priority')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color())->toggleable(),
                TextColumn::make('assignee.name')->label('Assigned to')->placeholder('Unassigned')->color(fn (Lead $record) => $record->assigned_to ? null : 'warning'),
                TextColumn::make('next_follow_up_at')->label('Next follow-up')->dateTime('d M H:i')->placeholder('—')->color(fn (Lead $record) => $record->next_follow_up_at?->isPast() && ! $record->status->isTerminal() ? 'danger' : null)->sortable(),
                TextColumn::make('sla')->label('Response')->state(fn (Lead $record) => $record->isSlaBreached() ? 'Overdue' : ($record->first_contacted_at ? 'Responded' : ($record->status === LeadStatus::New ? 'Waiting' : '—')))
                    ->badge()->color(fn (string $state) => match ($state) {
                        'Overdue' => 'danger', 'Responded' => 'success', 'Waiting' => 'warning', default => 'gray'
                    }),
                TextColumn::make('utm_source')->label('Source')->placeholder('direct')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('mine')->label('My leads')->query(fn (Builder $query) => $query->where('assigned_to', auth()->id())),
                Filter::make('unassigned')->label('Unassigned')->query(fn (Builder $query) => $query->unassigned()),
                Filter::make('overdue')->label('Follow-up overdue')->query(fn (Builder $query) => $query->followUpOverdue()),
                Filter::make('sla')->label('Response overdue')->query(fn (Builder $query) => $query->slaBreached()),
                Filter::make('stale')->label('No activity for 14 days')->query(fn (Builder $query) => $query->stale()),
                SelectFilter::make('status')->options(LeadStatus::options()),
                SelectFilter::make('priority')->options(LeadPriority::options()),
                SelectFilter::make('assigned_to')->label('Assigned to')->options(fn () => LeadActions::staff()),
                SelectFilter::make('request_type')->options(collect(RequestType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
            ])
            ->recordActions([EditAction::make(), ActionGroup::make(LeadActions::all())]);
    }

    public static function getRelations(): array
    {
        return [ActivitiesRelationManager::class, FollowUpsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListLeads::route('/'), 'edit' => EditLead::route('/{record}/edit')];
    }
}
