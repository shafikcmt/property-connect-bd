<?php

namespace App\Filament\Erp\Resources\ShareHoldings\Pages;

use App\Filament\Erp\Resources\ShareHoldings\ShareHoldingResource;
use App\Filament\Erp\Support\ErpActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewShareHolding extends ViewRecord
{
    protected static string $resource = ShareHoldingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ErpActions::recordPayment(),
            Action::make('statement')->label('Statement')->icon('heroicon-o-printer')->color('gray')->url(fn () => route('erp.statement', $this->record), true),
            ErpActions::cancelSale(),
        ];
    }
}
