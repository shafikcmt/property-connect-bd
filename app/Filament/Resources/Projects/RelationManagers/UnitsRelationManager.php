<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\Facing;
use App\Enums\UnitStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    protected static ?string $title = 'Units & availability';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('unit_no')->required()->maxLength(30)
                ->rule(fn () => Rule::unique('project_units', 'unit_no')->where('project_id', $this->getOwnerRecord()->id)->ignore($this->getMountedTableActionRecord()?->id)),
            Select::make('unit_type_id')->label('Unit type')->options(fn () => $this->getOwnerRecord()->unitTypes()->pluck('name', 'id')),
            TextInput::make('floor')->numeric(),
            Select::make('facing')->options(Facing::options()),
            TextInput::make('size_sqft')->numeric()->minValue(1),
            TextInput::make('price')->numeric()->minValue(0)->prefix('৳'),
            Select::make('status')->options(UnitStatus::options())->required()->default('available'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('unitType'))->columns([
            TextColumn::make('unit_no')->sortable()->searchable(),
            TextColumn::make('unitType.name')->label('Type'),
            TextColumn::make('floor')->sortable(),
            TextColumn::make('size_sqft')->suffix(' sqft'),
            TextColumn::make('price')->money('BDT')->placeholder('—'),
            TextColumn::make('status')->badge()->formatStateUsing(fn ($s) => $s->label())
                ->color(fn ($s) => match ($s) {
                    UnitStatus::Available => 'success', UnitStatus::Booked, UnitStatus::Hold => 'warning', UnitStatus::Sold => 'gray'
                }),
        ])->filters([SelectFilter::make('status')->options(UnitStatus::options())])
            ->headerActions([CreateAction::make()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
