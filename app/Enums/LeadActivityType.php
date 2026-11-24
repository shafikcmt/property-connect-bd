<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadActivityType: string
{
    case Note = 'note';
    case Call = 'call';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Meeting = 'meeting';
    case Visit = 'visit';
    case StatusChange = 'status_change';
    case Assignment = 'assignment';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /** A real conversation with the buyer (counts as the first response). */
    public function isContact(): bool
    {
        return in_array($this, [self::Call, self::Whatsapp, self::Email, self::Meeting, self::Visit], true);
    }

    /** Types a person can log by hand. @return array<string, string> */
    public static function loggable(): array
    {
        return collect([self::Note, self::Call, self::Whatsapp, self::Email, self::Meeting, self::Visit])->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
