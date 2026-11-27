<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\SiteVisitStatus;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\Project;
use App\Models\Property;
use App\Models\SiteVisit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** CRM numbers (docs/13 §10), computed from recorded data only: leads created inside the period and what happened to them. */
class CrmReport
{
    /** @return array<string, mixed> */
    public function build(CarbonInterface $from, CarbonInterface $to): array
    {
        $leads = fn () => Lead::query()->whereBetween('leads.created_at', [$from, $to]);
        $total = $leads()->count();
        $sla = (int) app(SettingsService::class)->get('leads.sla_minutes', 30);

        $responded = $leads()->whereNotNull('first_contacted_at');
        $median = (clone $responded)->selectRaw('percentile_cont(0.5) within group (order by extract(epoch from (first_contacted_at - created_at)) / 60) as m')->value('m');
        $withinSla = (clone $responded)->whereRaw("first_contacted_at <= created_at + (? * interval '1 minute')", [$sla])->count();

        $ids = $leads()->pluck('id');
        $reached = fn (array $statuses) => LeadStatusHistory::query()->whereIn('lead_id', $ids)->whereIn('to_status', $statuses)->distinct()->count('lead_id');
        $stage = fn (LeadStatus $s) => $reached([$s->value]);

        $visits = SiteVisit::query()->whereBetween('created_at', [$from, $to]);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'total' => $total,
            'repeat' => (int) $leads()->sum(DB::raw('inquiry_count - 1')),
            'unassigned_now' => Lead::unassigned()->count(),
            'response' => [
                'responded' => (clone $responded)->count(),
                'waiting' => $leads()->where('status', LeadStatus::New->value)->whereNull('first_contacted_at')->count(),
                'median_minutes' => $median !== null ? (float) $median : null,
                'within_sla' => $withinSla, 'sla_minutes' => $sla,
            ],
            'funnel' => [
                'New leads' => $total,
                'Contacted' => (clone $responded)->count(),
                'Qualified' => $stage(LeadStatus::Qualified),
                'Interested' => $stage(LeadStatus::Interested),
                'Site visit' => $stage(LeadStatus::SiteVisit),
                'Negotiation' => $stage(LeadStatus::Negotiation),
                'Converted' => $leads()->where('status', LeadStatus::Converted->value)->count(),
            ],
            'visits' => collect(SiteVisitStatus::cases())->mapWithKeys(fn (SiteVisitStatus $s) => [$s->label() => (clone $visits)->where('status', $s->value)->count()])->all(),
            'visit_leads' => (clone $visits)->where('status', SiteVisitStatus::Completed->value)->distinct()->count('lead_id'),
            'lost' => $leads()->whereIn('status', [LeadStatus::Lost->value, LeadStatus::NotInterested->value, LeadStatus::Invalid->value])->whereNotNull('lost_reason')
                ->selectRaw('lower(trim(lost_reason)) as reason, count(*) as n')->groupBy('reason')->orderByDesc('n')->limit(10)->pluck('n', 'reason')->all(),
            'sources' => $leads()->selectRaw("coalesce(nullif(utm_source, ''), 'direct') as source, count(*) as leads, count(first_contacted_at) as contacted, count(*) filter (where status = 'converted') as converted")
                ->groupBy('source')->orderByDesc('leads')->limit(10)->get()->map(fn ($r) => (array) $r->getAttributes())->all(),
            'types' => $leads()->selectRaw('request_type, count(*) as n')->groupBy('request_type')->orderByDesc('n')->pluck('n', 'request_type')->all(),
            'agents' => $leads()->whereNotNull('assigned_to')->join('users', 'users.id', '=', 'leads.assigned_to')
                ->selectRaw("users.name as agent, count(*) as assigned, count(leads.first_contacted_at) as contacted, count(*) filter (where leads.status = 'converted') as converted")
                ->groupBy('users.name')->orderByDesc('assigned')->get()->map(fn ($r) => (array) $r->getAttributes())->all(),
            'top_properties' => Property::query()->joinSub(Lead::query()->whereBetween('created_at', [$from, $to])->whereNotNull('property_id')->selectRaw('property_id, count(*) as n')->groupBy('property_id'), 'l', 'l.property_id', '=', 'properties.id')
                ->orderByDesc('l.n')->limit(5)->get(['properties.title', 'l.n'])->map(fn ($p) => ['name' => $p->title, 'n' => (int) $p->n])->all(),
            'top_projects' => Project::query()->joinSub(Lead::query()->whereBetween('created_at', [$from, $to])->whereNotNull('project_id')->selectRaw('project_id, count(*) as n')->groupBy('project_id'), 'l', 'l.project_id', '=', 'projects.id')
                ->orderByDesc('l.n')->limit(5)->get(['projects.name', 'l.n'])->map(fn ($p) => ['name' => $p->name, 'n' => (int) $p->n])->all(),
        ];
    }
}
