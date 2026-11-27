<?php

namespace App\Filament\Erp\Support;

use App\Enums\PaymentMethod;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Shareholder;
use App\Models\ShareHolding;
use App\Models\User;
use App\Services\Erp\ErpRuleViolation;
use App\Services\Erp\PaymentRecorder;
use App\Services\Erp\ShareSale;
use App\Support\Permissions;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;

/** Shared Developer ERP actions. The rules live in ShareSale and PaymentRecorder; these only collect input. */
class ErpActions
{
    public static function user(): User
    {
        return auth()->user();
    }

    public static function generateShares(): Action
    {
        return Action::make('generateShares')->label('Add shares')->icon('heroicon-o-plus-circle')
            ->visible(fn () => self::user()->can(Permissions::ERP_MANAGE))
            ->schema([
                TextInput::make('count')->label('How many shares')->numeric()->required()->minValue(1)->maxValue(500)->default(1),
                TextInput::make('area')->label('Land per share (শতাংশ / decimal)')->numeric()->minValue(0),
                TextInput::make('price')->label('List price per share')->numeric()->minValue(1)->prefix('৳'),
            ])
            ->action(self::guarded(fn (Project $owner, array $data) => app(ShareSale::class)->generateShares($owner, (int) $data['count'], filled($data['area'] ?? null) ? (float) $data['area'] : null, filled($data['price'] ?? null) ? (float) $data['price'] : null, self::user()), 'Shares added', 'owner'));
    }

    public static function sellShare(): Action
    {
        return Action::make('sell')->label('Sell')->icon('heroicon-o-banknotes')->color('primary')
            ->visible(fn (ProjectShare $record) => $record->status->value === 'available' && self::user()->can(Permissions::ERP_SELL))
            ->fillForm(fn (ProjectShare $record) => ['price' => $record->list_price, 'sold_on' => now()->toDateString()])
            ->schema(self::saleFields())
            ->action(self::guarded(fn (ProjectShare $record, array $data) => app(ShareSale::class)->sell($record, Shareholder::findOrFail($data['shareholder_id']), (float) $data['price'], Carbon::parse($data['sold_on']), self::user()), 'Share sold; instalment schedule created'));
    }

    /** @return array<int, Component> */
    public static function saleFields(): array
    {
        return [
            Select::make('shareholder_id')->label('Shareholder')->required()->searchable()
                ->options(fn () => Shareholder::query()->orderBy('name')->get()->mapWithKeys(fn (Shareholder $s) => [$s->id => $s->name.($s->phone ? ' · '.$s->phone : '')])->all())
                ->createOptionForm([
                    TextInput::make('name')->required()->maxLength(150),
                    TextInput::make('father_name')->maxLength(150),
                    TextInput::make('phone')->tel()->maxLength(20),
                ])
                ->createOptionUsing(function (array $data) {
                    $holder = (new Shareholder)->forceFill($data + ['developer_id' => self::user()->developer_id, 'created_by' => self::user()->id]);
                    $holder->save();

                    return $holder->id;
                }),
            TextInput::make('price')->label('Agreed price')->numeric()->required()->minValue(1)->prefix('৳'),
            DatePicker::make('sold_on')->label('Sale date')->required()->maxDate(now()),
        ];
    }

    public static function recordPayment(): Action
    {
        return Action::make('recordPayment')->label('Record payment')->icon('heroicon-o-banknotes')->color('primary')
            ->visible(fn (ShareHolding $record) => $record->isActive() && self::user()->can(Permissions::ERP_COLLECT))
            ->schema([
                TextInput::make('amount')->numeric()->required()->minValue(1)->prefix('৳'),
                DatePicker::make('received_on')->label('Received on')->required()->default(now())->maxDate(now()),
                Select::make('method')->options(PaymentMethod::options())->required()->default('cash')->live(),
                TextInput::make('reference')->maxLength(120)->required(fn ($get) => $get('method') !== 'cash')->helperText('Bank, cheque or transaction number. Required unless cash.'),
                Textarea::make('note')->rows(2)->maxLength(500),
            ])
            ->action(self::guarded(fn (ShareHolding $record, array $data) => app(PaymentRecorder::class)->record($record, (float) $data['amount'], Carbon::parse($data['received_on']), PaymentMethod::from($data['method']), $data['reference'] ?? null, $data['note'] ?? null, self::user()), 'Payment recorded'));
    }

    public static function cancelSale(): Action
    {
        return Action::make('cancelSale')->label('Cancel sale')->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (ShareHolding $record) => $record->isActive() && self::user()->can(Permissions::ERP_MANAGE))
            ->modalDescription('The share becomes available again. Payments already received stay on record; settling them with the buyer is done outside the system.')
            ->schema([Textarea::make('reason')->required()->rows(2)->maxLength(500)])
            ->action(self::guarded(fn (ShareHolding $record, array $data) => app(ShareSale::class)->cancel($record, $data['reason'], self::user()), 'Sale cancelled'));
    }

    /** Runs a service call; a broken rule becomes a red toast and keeps the dialog open. `$param` names the record argument. */
    public static function guarded(Closure $call, string $success, string $param = 'record'): Closure
    {
        return function (Action $action, array $data, $livewire = null, $record = null) use ($call, $success, $param): void {
            try {
                $call($param === 'owner' ? $livewire->getOwnerRecord() : $record, $data);
            } catch (ErpRuleViolation $e) {
                Notification::make()->title($e->getMessage())->danger()->send();
                $action->halt();

                return;
            }
            Notification::make()->title($success)->success()->send();
        };
    }
}
