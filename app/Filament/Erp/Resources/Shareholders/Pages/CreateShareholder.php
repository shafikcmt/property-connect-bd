<?php

namespace App\Filament\Erp\Resources\Shareholders\Pages;

use App\Filament\Erp\Resources\Shareholders\ShareholderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShareholder extends CreateRecord
{
    protected static string $resource = ShareholderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
