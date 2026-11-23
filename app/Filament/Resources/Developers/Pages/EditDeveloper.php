<?php

namespace App\Filament\Resources\Developers\Pages;

use App\Filament\Resources\Developers\DeveloperResource;
use App\Filament\Support\ListingActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDeveloper extends EditRecord
{
    protected static string $resource = DeveloperResource::class;

    protected function getHeaderActions(): array
    {
        return [...ListingActions::all(), DeleteAction::make(), RestoreAction::make()];
    }
}
