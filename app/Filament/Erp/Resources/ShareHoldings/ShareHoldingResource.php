<?php

namespace App\Filament\Erp\Resources\ShareHoldings;

use App\Filament\Erp\Resources\ShareHoldings\Pages\ListShareHoldings;
use App\Filament\Erp\Resources\ShareHoldings\Pages\ViewShareHolding;
use App\Filament\Erp\Resources\ShareHoldings\RelationManagers\InstalmentsRelationManager;
use App\Filament\Erp\Resources\ShareHoldings\RelationManagers\PaymentsRelationManager;
use App\Models\ShareHolding;
use App\Services\Erp\ErpSummary;
use App\Services\Erp\HoldingLedger;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** "Share sales": who holds which share, what they owe and what they have paid. Created from a project's share list. */
class ShareHoldingResource extends Resource
{
    protected static ?string $model = ShareHolding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Share sales';

    protected static ?string $modelLabel = 'share sale';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        $ledger = fn (ShareHolding $r) => new HoldingLedger($r);

        return $schema->components([
            Section::make('Sale')->columns(4)->schema([
                TextEntry::make('shareholder.name')->label('Shareholder'),
                TextEntry::make('share_label')->label('Share')->state(fn (ShareHolding $r) => $r->share->label().' · '.$r->share->project->name),
                TextEntry::make('sold_on')->date('d M Y'),
                TextEntry::make('status')->badge()->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                TextEntry::make('price')->formatStateUsing(fn ($state) => $state === null ? '—' : '৳ '.number_format((float) $state, 2)),
                TextEntry::make('total_due')->label('Total due')->state(fn (ShareHolding $r) => number_format($ledger($r)->totalDue(), 2)),
                TextEntry::make('paid')->state(fn (ShareHolding $r) => number_format($ledger($r)->paid(), 2)),
                TextEntry::make('balance')->state(fn (ShareHolding $r) => number_format($ledger($r)->balance(), 2)),
                TextEntry::make('overdue')->state(fn (ShareHolding $r) => number_format($ledger($r)->overdue(), 2))->color(fn (ShareHolding $r) => $ledger($r)->overdue() > 0 ? 'danger' : null),
                TextEntry::make('cancel_reason')->label('Cancelled because')->visible(fn (ShareHolding $r) => ! $r->isActive())->columnSpan(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['share.project', 'shareholder', 'payments', 'instalments']))
            ->defaultSort('sold_on', 'desc')
            ->columns([
                TextColumn::make('share.number')->label('Share')->prefix('#')->sortable(),
                TextColumn::make('share.project.name')->label('Project'),
                TextColumn::make('shareholder.name')->label('Shareholder')->searchable(),
                TextColumn::make('price')->formatStateUsing(fn ($state) => $state === null ? '—' : '৳ '.number_format((float) $state, 2))->sortable(),
                TextColumn::make('sold_on')->date('d M Y')->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('paid')->state(fn (ShareHolding $r) => number_format((new HoldingLedger($r))->paid(), 2)),
                TextColumn::make('balance')->state(fn (ShareHolding $r) => number_format((new HoldingLedger($r))->balance(), 2)),
                TextColumn::make('behind')->label('Overdue')->badge()->state(function (ShareHolding $r) {
                    $o = (new HoldingLedger($r))->overdue();

                    return $o > 0 ? number_format($o, 2) : 'On time';
                })->color(fn (string $state) => $state === 'On time' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['active' => 'Active', 'cancelled' => 'Cancelled'])->default('active'),
                Filter::make('overdue')->label('Overdue only')->query(function (Builder $query) {
                    $ids = collect(app(ErpSummary::class)->overdueHoldings(auth()->user()->developer_id, null, 500))->pluck('id');

                    return $query->whereIn('share_holdings.id', $ids);
                }),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [InstalmentsRelationManager::class, PaymentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListShareHoldings::route('/'), 'view' => ViewShareHolding::route('/{record}')];
    }
}
