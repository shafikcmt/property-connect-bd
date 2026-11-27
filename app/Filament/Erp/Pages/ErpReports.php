<?php

namespace App\Filament\Erp\Pages;

use App\Models\Payment;
use App\Services\Erp\ErpReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Collections, overdue ageing and project totals, with CSV downloads (opens in Excel). Visible to staff who can see payments. */
class ErpReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Money';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected string $view = 'filament.erp.pages.erp-reports';

    public string $range = '30';

    public static function canAccess(): bool
    {
        $u = auth()->user();

        return $u?->developer_id !== null && $u->can('viewAny', Payment::class);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @return array<string, string> */
    public static function ranges(): array
    {
        return ['30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months'];
    }

    /** @return array<string, mixed> */
    public function getReport(): array
    {
        $days = array_key_exists($this->range, self::ranges()) ? (int) $this->range : 30;

        return app(ErpReport::class)->build(auth()->user()->developer_id, now()->subDays($days)->startOfDay(), now());
    }
}
