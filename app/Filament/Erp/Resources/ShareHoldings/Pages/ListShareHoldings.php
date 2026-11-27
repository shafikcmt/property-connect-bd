<?php

namespace App\Filament\Erp\Resources\ShareHoldings\Pages;

use App\Filament\Erp\Resources\ShareHoldings\ShareHoldingResource;
use App\Filament\Erp\Support\ErpActions;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Shareholder;
use App\Services\Erp\ErpRuleViolation;
use App\Services\Erp\ShareSale;
use App\Support\Permissions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListShareHoldings extends ListRecords
{
    protected static string $resource = ShareHoldingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sellShare')->label('Sell a share')->icon('heroicon-o-banknotes')
                ->visible(fn () => auth()->user()->can(Permissions::ERP_SELL))
                ->schema([
                    Select::make('project_id')->label('Project')->required()->live()
                        ->options(fn () => Project::query()->where('developer_id', auth()->user()->developer_id)->whereHas('shares')->orderBy('name')->pluck('name', 'id')->all()),
                    Select::make('share_id')->label('Share')->required()
                        ->options(fn ($get) => $get('project_id') ? ProjectShare::query()->where('project_id', $get('project_id'))->where('status', 'available')->orderBy('number')->get()->mapWithKeys(fn ($s) => [$s->id => $s->label().($s->list_price ? ' · ৳'.number_format((float) $s->list_price) : '')])->all() : [])
                        ->live()->afterStateUpdated(fn ($state, $set) => $set('price', ProjectShare::find($state)?->list_price)),
                    ...ErpActions::saleFields(),
                ])
                ->action(function (Action $action, array $data): void {
                    try {
                        app(ShareSale::class)->sell(ProjectShare::findOrFail($data['share_id']), Shareholder::findOrFail($data['shareholder_id']), (float) $data['price'], Carbon::parse($data['sold_on']), auth()->user());
                    } catch (ErpRuleViolation $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                        $action->halt();

                        return;
                    }
                    Notification::make()->title('Share sold; instalment schedule created')->success()->send();
                }),
        ];
    }
}
