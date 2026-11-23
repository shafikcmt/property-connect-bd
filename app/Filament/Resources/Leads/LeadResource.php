<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadStatus;
use App\Enums\RequestType;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Basic pre-launch inquiry inbox (ADR-0016). The full CRM pipeline arrives in Phase 8. */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Inquiries';

    protected static ?string $navigationLabel = 'Inquiries';

    protected static ?string $modelLabel = 'inquiry';

    public static function getNavigationBadge(): ?string
    {
        $n = Lead::where('status', 'new')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact (private)')->columns(2)->schema([
                TextInput::make('name')->disabled(), TextInput::make('phone')->disabled(), TextInput::make('email')->disabled(),
                TextInput::make('request_type')->disabled()->formatStateUsing(fn ($state) => $state instanceof RequestType ? $state->label() : $state),
                Textarea::make('message')->disabled()->rows(4)->columnSpanFull(),
            ]),
            Section::make('Follow-up')->columns(2)->schema([
                Select::make('status')->options(LeadStatus::options())->required(),
                Textarea::make('internal_notes')->rows(4)->columnSpanFull()->helperText('Internal only. Never shown publicly.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['property', 'project']))
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('phone')->searchable()->copyable(),
                TextColumn::make('request_type')->badge()->formatStateUsing(fn ($s) => $s->label()),
                TextColumn::make('subject')->label('About')->state(fn (Lead $l) => $l->property?->title ?? $l->project?->name ?? '—')->limit(30),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($s) => $s->label())->color(fn ($s) => $s === LeadStatus::New ? 'warning' : 'gray'),
                TextColumn::make('utm_source')->label('Source')->placeholder('direct')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::options()),
                SelectFilter::make('request_type')->options(collect(RequestType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLeads::route('/'), 'edit' => EditLead::route('/{record}/edit')];
    }
}
