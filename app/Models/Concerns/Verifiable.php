<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * "Verified" is always derived from a verification record (ADR-0013); the
 * is_verified/verified_at columns are a denormalised cache kept in sync here.
 */
trait Verifiable
{
    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable')->latest('id');
    }

    public function markVerified(User $by, ?string $method = null, ?string $notes = null): Verification
    {
        $record = $this->verifications()->create([
            'verified_by' => $by->id, 'verified_on' => now()->toDateString(), 'method' => $method, 'notes' => $notes, 'status' => 'verified',
        ]);
        $this->forceFill(['is_verified' => true, 'verified_at' => now()])->saveQuietly();

        return $record;
    }

    public function revokeVerification(string $reason): void
    {
        $this->verifications()->where('status', 'verified')->update(['status' => 'revoked', 'revoked_at' => now(), 'revoked_reason' => $reason]);
        $this->forceFill(['is_verified' => false, 'verified_at' => null])->saveQuietly();
    }
}
