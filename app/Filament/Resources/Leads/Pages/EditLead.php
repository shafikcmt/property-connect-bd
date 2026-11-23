<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Services\AuditLogger;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    /** Only status + notes are editable; contact fields are read-only (status is not mass-assignable on purpose). */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $old = $record->status;
        $record->status = LeadStatus::from($data['status']);
        $record->internal_notes = $data['internal_notes'] ?? null;
        $record->save();

        if ($old !== $record->status) {
            app(AuditLogger::class)->log('lead.status_changed', $record, ['status' => $old->value], ['status' => $record->status->value]);
        }

        return $record;
    }

    protected function afterFill(): void
    {
        // Viewing contact details of an inquiry is a privacy-relevant read.
        app(AuditLogger::class)->log('lead.viewed', $this->record);
    }
}
