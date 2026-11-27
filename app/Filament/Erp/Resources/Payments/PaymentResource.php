<?php

namespace App\Filament\Erp\Resources\Payments;

use App\Enums\PaymentMethod;
use App\Filament\Erp\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Every payment and reversal, newest first. Read-only: money records are written only through PaymentRecorder. */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Money';

    protected static ?string $navigationLabel = 'Payments';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['holding.shareholder', 'holding.share', 'recorder']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('receipt_no')->label('Receipt')->searchable()->weight('medium'),
                TextColumn::make('received_on')->date('d M Y')->sortable(),
                TextColumn::make('holding.shareholder.name')->label('Shareholder')->searchable(),
                TextColumn::make('share')->state(fn (Payment $r) => '#'.$r->holding->share->number),
                TextColumn::make('kind')->badge()->color(fn (string $state) => $state === 'reversal' ? 'danger' : 'success'),
                TextColumn::make('signed')->label('Amount')->state(fn (Payment $r) => number_format($r->signedAmount(), 2)),
                TextColumn::make('method')->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('reference')->placeholder('—')->searchable(),
                TextColumn::make('recorder.name')->label('Recorded by')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('method')->options(PaymentMethod::options()),
                SelectFilter::make('kind')->options(['payment' => 'Payment', 'reversal' => 'Reversal']),
                Filter::make('period')->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data) => $query->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('received_on', '>=', $d))->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('received_on', '<=', $d))),
            ])
            ->recordActions([Action::make('receipt')->label('Receipt')->icon('heroicon-o-printer')->url(fn (Payment $record) => route('erp.receipt', $record), true)]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }
}
