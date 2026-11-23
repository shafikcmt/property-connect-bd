<?php

declare(strict_types=1);

namespace App\Enums;

enum RequestType: string
{
    case PropertyInquiry = 'property_inquiry';
    case ProjectInquiry = 'project_inquiry';
    case DeveloperInquiry = 'developer_inquiry';
    case Contact = 'contact';
    case SiteVisit = 'site_visit';
    case Sell = 'sell';
    case Rent = 'rent';
    case Valuation = 'valuation';
    case Assistance = 'assistance';
    case Submission = 'submission';

    public function label(): string
    {
        return __(ucwords(str_replace('_', ' ', $this->value)));
    }
}
