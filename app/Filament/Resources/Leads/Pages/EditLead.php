<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadPriority;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Support\LeadActions;
use App\Services\AuditLogger;
use App\Services\LeadRuleViolation;
use App\Services\LeadWorkflow;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return LeadActions::all();
    }

    /** phone/email are hidden from serialisation on purpose, so the read-only fields are filled from the model here. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $lead = $this->record;

        return array_merge($data, [
            'phone' => $lead->phone, 'email' => $lead->email, 'request_type' => $lead->request_type->label(),
            'status' => $lead->status->label(), 'assignee' => $lead->assignee?->name ?? 'Unassigned', 'priority' => $lead->priority->value,
            'buy_timeline' => $lead->buy_timeline?->value,
        ]);
    }

    /** Only priority + notes are editable here; status, assignment and follow-ups go through LeadWorkflow actions. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->priority = LeadPriority::from($data['priority']);
        $record->internal_notes = $data['internal_notes'] ?? null;
        $record->save();

        try {
            app(LeadWorkflow::class)->updateNeeds($record, $data, auth()->user());
        } catch (LeadRuleViolation $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt();
        }

        return $record;
    }

    protected function afterFill(): void
    {
        // Viewing contact details of an inquiry is a privacy-relevant read.
        app(AuditLogger::class)->log('lead.viewed', $this->record);
    }
}
