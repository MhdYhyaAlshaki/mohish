<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('email')->email()->required()->maxLength(190),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('admin_role')
                    ->options([
                        'admin' => 'Admin',
                        'analyst' => 'Analyst',
                    ])
                    ->required(),
                Select::make('payout_tier_id')
                    ->relationship('payoutTier', 'label')
                    ->searchable()
                    ->preload(),
                Toggle::make('is_flagged'),
                TextInput::make('points')->numeric()->required(),
            ]);
    }
}
