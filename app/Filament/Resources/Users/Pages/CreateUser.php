<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Erp\UserDeveloperLink;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** `status` is deliberately not mass-assignable on User; it is set explicitly here. */
    protected function handleRecordCreation(array $data): Model
    {
        $status = UserStatus::tryFrom((string) ($data['status'] ?? '')) ?? UserStatus::Active;
        unset($data['status']);
        $developerId = filled($data['developer_id'] ?? null) ? (int) $data['developer_id'] : null;
        unset($data['developer_id']);
        UserDeveloperLink::validate((array) ($this->data['roles'] ?? []), $developerId);

        $user = new User($data);
        $user->status = $status;
        $user->developer_id = $developerId; // privilege-relevant, so never mass-assignable
        $user->save();

        // The account holder must still confirm their email before accessing the panel.
        $user->sendEmailVerificationNotification();

        return $user;
    }

    protected function afterCreate(): void
    {
        // Roles and status are privilege-relevant: audit them (never the password).
        app(AuditLogger::class)->log('user.created', $this->record, null, [
            'roles' => $this->record->roles()->pluck('name')->all(),
            'status' => $this->record->status->value,
            'developer_id' => $this->record->developer_id,
        ]);
    }
}
