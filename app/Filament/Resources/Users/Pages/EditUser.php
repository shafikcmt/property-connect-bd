<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $status = isset($data['status']) ? UserStatus::tryFrom((string) $data['status']) : null;
        unset($data['status']);

        $record->fill($data);
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
        ]);
    }
}
