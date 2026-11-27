<?php

namespace App\Filament\Erp\Resources\FlatAllocations\Pages;

use App\Filament\Erp\Resources\FlatAllocations\FlatAllocationResource;
use App\Models\ShareHolding;
use App\Services\Erp\FlatAllocator;
use App\Support\Permissions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListFlatAllocations extends ListRecords
{
    protected static string $resource = FlatAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('allocate')->label('Allocate a flat')->icon('heroicon-o-plus')
                ->visible(fn () => auth()->user()->can(Permissions::ERP_MANAGE))
                ->schema([
                    Select::make('holding')->label('Shareholder (sale without a flat)')->required()->searchable()->options(fn () => FlatAllocationResource::unallocatedOptions()),
                    TextInput::make('building')->maxLength(40)->placeholder('e.g. Building A'),
                    TextInput::make('floor')->required()->maxLength(20),
                    TextInput::make('flat_no')->label('Flat number')->required()->maxLength(20),
                    Select::make('method')->required()->options(['lottery' => 'Lottery', 'choice' => 'Buyer’s choice', 'assigned' => 'Assigned by developer']),
                    DatePicker::make('on')->label('Allocation date')->required()->default(now())->maxDate(now()),
                ])
                ->action(fn (Action $action, array $data) => FlatAllocationResource::run(
                    fn () => app(FlatAllocator::class)->allocate(ShareHolding::query()->findOrFail($data['holding']), $data['building'] ?? '', $data['floor'], $data['flat_no'], $data['method'], Carbon::parse($data['on']), auth()->user()),
                    'Flat allocated', $action)),
        ];
    }
}
