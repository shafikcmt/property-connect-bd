<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Account'))->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->email()->required()->maxLength(255)
                    ->unique(table: 'users', column: 'email', ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at')),
                TextInput::make('phone')->tel()->maxLength(20)->regex('/^\+?[0-9\s\-]{7,20}$/')
                    ->unique(table: 'users', column: 'phone', ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at')),
                TextInput::make('password')->password()->revealable()
                    ->rule(Password::min(12)->letters()->numbers())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? __('Leave blank to keep the current password.') : null),
                Select::make('status')->options(collect(UserStatus::cases())->mapWithKeys(fn ($state) => [$state->value => ucfirst($state->value)]))
                    ->required()->default(UserStatus::Active->value)
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())), // cannot suspend yourself
                Select::make('roles')->relationship('roles', 'name', fn (Builder $query) => auth()->user()?->hasRole(RoleName::SuperAdmin->value)
                    ? $query
                    : $query->where('name', '!=', RoleName::SuperAdmin->value))
                    ->multiple()->preload()
                    // Server-side guard: option filtering alone is not validation.
                    ->rules([fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                        $superId = Role::where('name', RoleName::SuperAdmin->value)->value('id');
                        if (in_array($superId, (array) $value, false) && ! auth()->user()?->hasRole(RoleName::SuperAdmin->value)) {
                            $fail(__('You are not allowed to assign the Super Admin role.'));
                        }
                    }])
                    ->getOptionLabelFromRecordUsing(fn ($record) => RoleName::tryFrom($record->name)?->label() ?? $record->name),
            ]),
        ]);
    }
}
