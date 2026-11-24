<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Interested = 'interested';
    case SiteVisit = 'site_visit';
    case Negotiation = 'negotiation';
    case Converted = 'converted';
    case Lost = 'lost';
    case NotInterested = 'not_interested';
    case Invalid = 'invalid';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return __(ucwords(str_replace('_', ' ', $this->value)));
    }

    /** Closed outcomes; only a manager can reopen them. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Converted, self::Lost, self::NotInterested, self::Invalid, self::Duplicate], true);
    }

    /** Position in the pipeline (terminal states are outside it). Used to detect backwards moves. */
    public function rank(): ?int
    {
        $order = [self::New, self::Contacted, self::Qualified, self::Interested, self::SiteVisit, self::Negotiation];
        $i = array_search($this, $order, true);

        return $i === false ? null : $i;
    }

    /** @return list<self> */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => ! $s->isTerminal()));
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
