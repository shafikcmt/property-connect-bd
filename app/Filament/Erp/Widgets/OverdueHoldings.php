<?php

namespace App\Filament\Erp\Widgets;

use App\Filament\Erp\Resources\ShareHoldings\ShareHoldingResource;
use App\Models\ShareHolding;
use App\Services\Erp\ErpSummary;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Shareholders who are behind on their instalments, largest amount first. */
class OverdueHoldings extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Shareholders behind on instalments';

    public static function canView(): bool
    {
        $u = auth()->user();

        return $u?->developer_id !== null && ($u->can(Permissions::ERP_COLLECT) || $u->can(Permissions::ERP_MANAGE));
    }

    public function table(Table $table): Table
    {
        $overdue = collect(app(ErpSummary::class)->overdueHoldings(auth()->user()->developer_id))->pluck('overdue', 'id');

        return $table
            ->query(fn (): Builder => ShareHolding::query()->whereIn('id', $overdue->keys())->with(['shareholder', 'share.project']))
            ->paginated(false)
            ->emptyStateHeading('Nobody is behind')
            ->columns([
                TextColumn::make('shareholder.name')->label('Shareholder')->weight('medium'),
                TextColumn::make('share.project.name')->label('Project'),
                TextColumn::make('share.number')->label('Share')->prefix('#'),
                TextColumn::make('behind')->label('Overdue')->state(fn (ShareHolding $r) => '৳ '.number_format((float) ($overdue[$r->id] ?? 0)))->color('danger'),
            ])
            ->recordActions([Action::make('open')->label('Open')->url(fn (ShareHolding $record) => ShareHoldingResource::getUrl('view', ['record' => $record]))]);
    }
}
