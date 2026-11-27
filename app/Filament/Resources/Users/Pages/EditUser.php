<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Erp\UserDeveloperLink;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markVerified')->label(__('Mark email as verified'))->icon('heroicon-o-check-badge')->color('gray')->requiresConfirmation()
                ->modalDescription(__('Use this when you created the account yourself and confirmed the email address with the person. Recorded in the audit log.'))
                ->visible(fn (User $record) => ! $record->hasVerifiedEmail())
                ->action(function (User $record): void {
                    $record->markEmailAsVerified();
                    app(AuditLogger::class)->log('user.email_marked_verified', $record);
                    Notification::make()->title(__('Email marked as verified'))->success()->send();
                }),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $status = isset($data['status']) ? UserStatus::tryFrom((string) $data['status']) : null;
        unset($data['status']);
        $developerId = filled($data['developer_id'] ?? null) ? (int) $data['developer_id'] : null;
        unset($data['developer_id']);
        UserDeveloperLink::validate((array) ($this->data['roles'] ?? []), $developerId);

        $record->fill($data);
        $record->developer_id = $developerId; // privilege-relevant, so never mass-assignable
        if ($status) {
            $record->status = $status;
        }
        $record->save();

        return $record;
    }

    protected function afterSave(): void
    {
        app(AuditLogger::class)->log('user.updated', $this->record, null, [
            'roles' => $this->record->roles()->pluck('name')->all(),
            'status' => $this->record->status->value,
            'developer_id' => $this->record->developer_id,
        ]);
    }
}
