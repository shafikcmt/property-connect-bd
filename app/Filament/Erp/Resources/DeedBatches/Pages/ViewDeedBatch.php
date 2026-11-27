<?php

namespace App\Filament\Erp\Resources\DeedBatches\Pages;

use App\Filament\Erp\Resources\DeedBatches\DeedBatchResource;
use App\Models\DeedBatch;
use App\Services\Erp\DeedRegistry;
use App\Services\Erp\ErpRuleViolation;
use App\Support\Permissions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDeedBatch extends ViewRecord
{
    protected static string $resource = DeedBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('register')->label('Mark as registered')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => ! $this->record->isRegistered() && auth()->user()->can(Permissions::ERP_MANAGE))
                ->requiresConfirmation()->modalDescription('A registered deed can no longer change its list of shareholders. The public project page will count these shareholders as "deed registered".')
                ->schema([
                    TextInput::make('deed_number')->required()->maxLength(60),
                    TextInput::make('office')->label('Sub-registry office')->required()->maxLength(150),
                    DatePicker::make('registered_on')->required()->maxDate(now()),
                ])
                ->action(function (Action $action, array $data): void {
                    try {
                        app(DeedRegistry::class)->register($this->record, $data['deed_number'], $data['office'], Carbon::parse($data['registered_on']), auth()->user());
                    } catch (ErpRuleViolation $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                        $action->halt();

                        return;
                    }
                    $this->record = DeedBatch::query()->findOrFail($this->record->getKey());
                    Notification::make()->title('Deed registered')->success()->send();
                }),
        ];
    }
}
