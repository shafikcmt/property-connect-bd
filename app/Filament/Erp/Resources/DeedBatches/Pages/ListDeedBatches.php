<?php

namespace App\Filament\Erp\Resources\DeedBatches\Pages;

use App\Filament\Erp\Resources\DeedBatches\DeedBatchResource;
use App\Models\Project;
use App\Services\Erp\DeedRegistry;
use App\Services\Erp\ErpRuleViolation;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDeedBatches extends ListRecords
{
    protected static string $resource = DeedBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newDeed')->label('New deed batch')->icon('heroicon-o-plus')
                ->visible(fn () => auth()->user()->can(Permissions::ERP_MANAGE))
                ->schema([
                    Select::make('project_id')->label('Project')->required()
                        ->options(fn () => Project::query()->where('developer_id', auth()->user()->developer_id)->whereHas('shares')->orderBy('name')->pluck('name', 'id')->all()),
                    Textarea::make('notes')->rows(2)->maxLength(1000),
                ])
                ->action(function (Action $action, array $data) {
                    try {
                        $batch = app(DeedRegistry::class)->createBatch(Project::query()->where('developer_id', auth()->user()->developer_id)->findOrFail($data['project_id']), $data['notes'] ?? null, auth()->user());
                    } catch (ErpRuleViolation $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                        $action->halt();

                        return;
                    }
                    Notification::make()->title('Draft deed created. Add the shareholders it lists.')->success()->send();

                    return redirect(DeedBatchResource::getUrl('view', ['record' => $batch]));
                }),
        ];
    }
}
