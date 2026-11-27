<?php

namespace App\Filament\Erp\Resources\ShareHoldings\RelationManagers;

use App\Models\Payment;
use App\Services\Erp\ErpRuleViolation;
use App\Services\Erp\PaymentRecorder;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['reversal', 'recorder']))
            ->columns([
                TextColumn::make('receipt_no')->label('Receipt')->weight('medium'),
                TextColumn::make('received_on')->date('d M Y'),
                TextColumn::make('kind')->badge()->color(fn (string $state) => $state === 'reversal' ? 'danger' : 'success'),
                TextColumn::make('signed')->label('Amount')->state(fn (Payment $r) => number_format($r->signedAmount(), 2))->color(fn (Payment $r) => $r->isReversal() ? 'danger' : null),
                TextColumn::make('method')->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('reference')->placeholder('—'),
                TextColumn::make('recorder.name')->label('Recorded by')->placeholder('—'),
                TextColumn::make('note')->wrap()->placeholder('—')->limit(40),
            ])
            ->recordActions([
                Action::make('receipt')->label('Receipt')->icon('heroicon-o-printer')->color('gray')->url(fn (Payment $record) => route('erp.receipt', $record), true),
                Action::make('reverse')->label('Reverse')->icon('heroicon-o-arrow-uturn-left')->color('danger')
                    ->visible(fn (Payment $record) => ! $record->isReversal() && $record->reversal === null && auth()->user()->can(Permissions::ERP_MANAGE))
                    ->modalDescription('A reversal cancels this payment in the books. The original stays on record.')
                    ->schema([Textarea::make('reason')->required()->rows(2)->maxLength(500)])
                    ->action(function (Action $action, Payment $record, array $data): void {
                        try {
                            app(PaymentRecorder::class)->reverse($record, $data['reason'], auth()->user());
                        } catch (ErpRuleViolation $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            $action->halt();

                            return;
                        }
                        Notification::make()->title('Payment reversed')->success()->send();
                    }),
            ]);
    }
}
