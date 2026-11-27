<?php

namespace App\Filament\Erp\Resources\Projects\Pages;

use App\Filament\Erp\Resources\Projects\ErpProjectResource;
use Filament\Resources\Pages\ListRecords;

class ListErpProjects extends ListRecords
{
    protected static string $resource = ErpProjectResource::class;
}
