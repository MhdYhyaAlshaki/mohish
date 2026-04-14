<?php

namespace App\Filament\Resources\PayoutTiers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PayoutTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(24),
                TextInput::make('label')->required()->maxLength(60),
                TextInput::make('payout_multiplier')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
