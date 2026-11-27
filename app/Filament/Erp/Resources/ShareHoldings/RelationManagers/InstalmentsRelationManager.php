<?php

namespace App\Filament\Erp\Resources\ShareHoldings\RelationManagers;

use App\Models\ShareInstalment;
use App\Services\Erp\HoldingLedger;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstalmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'instalments';

    protected static ?string $title = 'Instalment schedule';

    public function isReadOnly(): bool
    {
        return true;
    }

    /** @return array<int, array{paid: float, status: string}> */
    private function ledger(): array
    {
        return collect((new HoldingLedger($this->getOwnerRecord()))->rows())->mapWithKeys(fn ($r) => [$r['instalment']->id => ['paid' => $r['paid'], 'status' => $r['status']]])->all();
    }

    public function table(Table $table): Table
    {
        $rows = $this->ledger();

        return $table->paginated(false)->columns([
            TextColumn::make('label')->weight('medium'),
            TextColumn::make('due_on')->date('d M Y'),
            TextColumn::make('amount')->formatStateUsing(fn ($state) => $state === null ? '—' : '৳ '.number_format((float) $state, 2)),
            TextColumn::make('paid')->state(fn (ShareInstalment $r) => number_format($rows[$r->id]['paid'] ?? 0, 2)),
            TextColumn::make('state')->label('Status')->badge()->state(fn (ShareInstalment $r) => ucfirst($rows[$r->id]['status'] ?? 'upcoming'))
                ->color(fn (string $state) => match ($state) {
                    'Paid' => 'success', 'Overdue' => 'danger', 'Due', 'Partial' => 'warning', default => 'gray'
                }),
        ]);
    }
}
