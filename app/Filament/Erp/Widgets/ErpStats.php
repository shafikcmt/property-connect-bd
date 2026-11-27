<?php

namespace App\Filament\Erp\Widgets;

use App\Services\Erp\ErpSummary;
use App\Support\Permissions;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Company-wide numbers for the signed-in developer. */
class ErpStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Overview';

    public static function canView(): bool
    {
        $u = auth()->user();

        return $u?->developer_id !== null && $u->can(Permissions::ERP_VIEW);
    }

    protected function getStats(): array
    {
        $s = app(ErpSummary::class)->forDeveloper(auth()->user()->developer_id);
        $money = fn (float $v) => '৳ '.number_format($v);
        $stats = [
            Stat::make('Shares sold', $s['sold'].' of '.$s['shares'])->description($s['available'].' still available')->icon('heroicon-o-receipt-percent'),
        ];
        if (auth()->user()->can(Permissions::ERP_COLLECT) || auth()->user()->can(Permissions::ERP_MANAGE)) {
            $stats[] = Stat::make('Collected this month', $money($s['collected_month']))->icon('heroicon-o-banknotes')->color('success');
            $stats[] = Stat::make('Total received', $money($s['paid']))->description('of '.$money($s['due']).' due')->icon('heroicon-o-currency-bangladeshi');
            $stats[] = Stat::make('Outstanding', $money($s['balance']))->icon('heroicon-o-clock');
            $stats[] = Stat::make('Overdue', $money($s['overdue']))->description($s['overdue_holdings'].' shareholders behind')->icon('heroicon-o-exclamation-triangle')->color($s['overdue'] > 0 ? 'danger' : 'success');
        }

        return $stats;
    }
}
