<?php

namespace App\Filament\Erp\Resources\FlatAllocations;

use App\Filament\Erp\Resources\FlatAllocations\Pages\ListFlatAllocations;
use App\Models\FlatAllocation;
use App\Models\ShareHolding;
use App\Services\Erp\ErpRuleViolation;
use App\Services\Erp\FlatAllocator;
use App\Support\Permissions;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Which flat each shareholder gets, and the handover checklist. */
class FlatAllocationResource extends Resource
{
    protected static ?string $model = FlatAllocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'Projects';

    protected static ?string $navigationLabel = 'Flats & handover';

    protected static ?string $modelLabel = 'flat allocation';

    public static function canCreate(): bool
    {
        return false;
    }

    /** @param callable():mixed $call */
    public static function run(callable $call, string $ok, Action $action): void
    {
        try {
            $call();
        } catch (ErpRuleViolation $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $action->halt();

            return;
        }
        Notification::make()->title($ok)->success()->send();
    }

    public static function table(Table $table): Table
    {
        $manage = fn () => auth()->user()->can(Permissions::ERP_MANAGE);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['project', 'holding.shareholder', 'holding.share']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('project.name')->label('Project'),
                TextColumn::make('flat')->state(fn (FlatAllocation $r) => $r->flatLabel()),
                TextColumn::make('holding.shareholder.name')->label('Shareholder')->searchable(),
                TextColumn::make('holding.share.number')->label('Share')->prefix('#'),
                TextColumn::make('method')->badge(),
                TextColumn::make('allocated_on')->date('d M Y'),
                TextColumn::make('checklist_done')->label('Checklist')->state(fn (FlatAllocation $r) => count((array) $r->checklist).'/'.count(FlatAllocation::CHECKLIST)),
                TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'handed_over' ? 'success' : 'warning')->formatStateUsing(fn (string $state) => $state === 'handed_over' ? 'Handed over' : 'Allocated'),
            ])
            ->recordActions([
                Action::make('checklist')->label('Checklist')->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn (FlatAllocation $record) => $record->status === 'allocated' && $manage())
                    ->fillForm(fn (FlatAllocation $record) => ['done' => (array) $record->checklist])
                    ->schema([CheckboxList::make('done')->label('Completed')->options(FlatAllocation::CHECKLIST)])
                    ->action(fn (Action $action, array $data, FlatAllocation $record) => self::run(fn () => app(FlatAllocator::class)->saveChecklist($record, $data['done'] ?? [], auth()->user()), 'Checklist saved', $action)),
                Action::make('handover')->label('Hand over')->icon('heroicon-o-key')->color('success')
                    ->visible(fn (FlatAllocation $record) => $record->status === 'allocated' && $manage())
                    ->schema([DatePicker::make('on')->label('Handover date')->required()->default(now())->maxDate(now())])
                    ->requiresConfirmation()
                    ->action(fn (Action $action, array $data, FlatAllocation $record) => self::run(fn () => app(FlatAllocator::class)->handOver($record, Carbon::parse($data['on']), auth()->user()), 'Flat handed over', $action)),
                Action::make('release')->label('Release flat')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                    ->visible(fn (FlatAllocation $record) => $record->status === 'allocated' && $manage())
                    ->action(fn (Action $action, FlatAllocation $record) => self::run(fn () => app(FlatAllocator::class)->release($record, auth()->user()), 'Flat released', $action)),
            ]);
    }

    /** Active sales that do not have a flat yet, for the "Allocate" form. @return array<int, string> */
    public static function unallocatedOptions(): array
    {
        return ShareHolding::query()->with(['share.project', 'shareholder'])->where('status', 'active')
            ->whereNotIn('id', FlatAllocation::query()->select('share_holding_id'))->get()
            ->mapWithKeys(fn ($h) => [$h->id => $h->share->project->name.' · #'.$h->share->number.' · '.$h->shareholder->name])->all();
    }

    public static function getPages(): array
    {
        return ['index' => ListFlatAllocations::route('/')];
    }
}
