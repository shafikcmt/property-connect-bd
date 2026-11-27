<?php

namespace App\Filament\Erp\Resources\Payments\Pages;

use App\Filament\Erp\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}
