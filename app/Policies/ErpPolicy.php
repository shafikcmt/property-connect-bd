<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Developer ERP access (ADR-0019). `$readPermissions` decide who may see a kind of record, `$writePermissions` who may
 * create or change it; every check also requires the record to belong to the user's own developer. Deleting is never
 * allowed: sales are cancelled and money is reversed, so history stays intact.
 */
abstract class ErpPolicy
{
    /** @var list<string> */
    protected array $read = [Permissions::ERP_VIEW];

    /** @var list<string> */
    protected array $write = [Permissions::ERP_MANAGE];

    public function viewAny(User $u): bool
    {
        return $u->developer_id !== null && $this->canAny($u, $this->read);
    }

    public function view(User $u, Model $m): bool
    {
        return $this->viewAny($u) && $m->developer_id === $u->developer_id;
    }

    public function create(User $u): bool
    {
        return $u->developer_id !== null && $this->canAny($u, $this->write);
    }

    public function update(User $u, Model $m): bool
    {
        return $this->create($u) && $m->developer_id === $u->developer_id;
    }

    public function delete(User $u, Model $m): bool
    {
        return false;
    }

    /** @param list<string> $permissions */
    private function canAny(User $u, array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($u->can($p)) {
                return true;
            }
        }

        return false;
    }
}
