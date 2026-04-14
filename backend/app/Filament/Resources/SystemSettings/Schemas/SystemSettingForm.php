<?php

namespace App\Filament\Resources\SystemSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SystemSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')->required()->maxLength(80),
                TextInput::make('group')->default('profit')->required()->maxLength(40),
                TextInput::make('value')->required(),
                Select::make('value_type')
                    ->options([
                        'string' => 'string',
                        'int' => 'int',
                        'float' => 'float',
                        'bool' => 'bool',
                    ])
                    ->required(),
            ]);
    }
}
