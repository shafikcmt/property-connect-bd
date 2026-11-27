<?php

namespace App\Filament\Pages;

use App\Services\CrmReport;
use App\Support\Permissions;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Lead and visit numbers for managers. Read-only; no contact details are shown. */
class CrmReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Inquiries';

    protected static ?string $navigationLabel = 'CRM reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'CRM reports';

    protected string $view = 'filament.pages.crm-reports';

    public string $range = '30';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permissions::LEADS_MANAGE);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @return array<string, string> */
    public static function ranges(): array
    {
        return ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months'];
    }

    /** @return array<string, mixed> */
    public function getReport(): array
    {
        $days = array_key_exists($this->range, self::ranges()) ? (int) $this->range : 30;

        return app(CrmReport::class)->build(now()->subDays($days)->startOfDay(), now());
    }
}
