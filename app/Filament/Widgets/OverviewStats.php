<?php

namespace App\Filament\Widgets;

use App\Enums\PublishStatus;
use App\Models\Developer;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Property;
use App\Models\SiteVisit;
use App\Support\Permissions;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Real counts only — each stat appears only for staff allowed to see that data. */
class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Overview';

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can(Permissions::LISTINGS_VIEW) || $user?->can(Permissions::LISTINGS_MANAGE)) {
            $stats[] = Stat::make('Published properties', Property::visible()->count())->icon('heroicon-o-home-modern');
            $stats[] = Stat::make('Awaiting review', Property::where('publish_status', PublishStatus::PendingReview->value)->count())->icon('heroicon-o-clock')
                ->description('Owner submissions')->color('warning');
            $stats[] = Stat::make('Published projects', Project::published()->count())->icon('heroicon-o-building-office');
            $stats[] = Stat::make('Published developers', Developer::published()->count())->icon('heroicon-o-building-office-2');
        }
        if ($user?->can(Permissions::LEADS_MANAGE)) {
            $stats[] = Stat::make('New inquiries', Lead::where('status', 'new')->count())->icon('heroicon-o-inbox-arrow-down')->color('success');
            $stats[] = Stat::make('Site visit requests', SiteVisit::where('status', 'requested')->count())->icon('heroicon-o-calendar');
        }
        if ($user?->can(Permissions::LEADS_MANAGE) || $user?->can(Permissions::LEADS_WORK)) {
            $mine = Lead::visibleTo($user);
            $stats[] = Stat::make('Follow-ups overdue', (clone $mine)->followUpOverdue()->count())->icon('heroicon-o-clock')->color('danger')->description('Open leads past their follow-up time');
            $stats[] = Stat::make('Response overdue', (clone $mine)->slaBreached()->count())->icon('heroicon-o-bell-alert')->color('danger')->description('New leads not contacted in time');
            $stats[] = Stat::make('Needs attention', (clone $mine)->stale()->count())->icon('heroicon-o-exclamation-triangle')->color('warning')->description('No activity for 14 days');
        }
        if ($user?->can(Permissions::LEADS_ASSIGN)) {
            $stats[] = Stat::make('Unassigned leads', Lead::unassigned()->count())->icon('heroicon-o-user-plus')->color('warning');
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can(Permissions::LISTINGS_VIEW) || $user?->can(Permissions::LEADS_MANAGE) || $user?->can(Permissions::LEADS_WORK));
    }
}
