<?php

namespace App\Filament\Erp\Resources\Shareholders\Pages;

use App\Filament\Erp\Resources\Shareholders\ShareholderResource;
use App\Services\AuditLogger;
use Filament\Resources\Pages\EditRecord;

class EditShareholder extends EditRecord
{
    protected static string $resource = ShareholderResource::class;

    protected function afterSave(): void
    {
        // Personal data changed: record who did it (never the values).
        app(AuditLogger::class)->log('erp.shareholder_updated', $this->record);
    }
}
