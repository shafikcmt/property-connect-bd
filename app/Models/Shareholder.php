<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToDeveloper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A buyer of shares. Contact and ID data are private: the NID is stored encrypted and only its last four digits are shown. */
class Shareholder extends Model
{
    use BelongsToDeveloper, SoftDeletes;

    protected $guarded = ['developer_id', 'nid_last4'];

    protected $hidden = ['nid'];

    protected function casts(): array
    {
        return ['nid' => 'encrypted'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $s): void {
            $digits = preg_replace('/\D/', '', (string) $s->nid);
            $s->nid_last4 = $digits !== '' ? substr($digits, -4) : null;
        });
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(ShareHolding::class);
    }

    public function maskedNid(): string
    {
        return $this->nid_last4 ? '••••••'.$this->nid_last4 : '—';
    }
}
