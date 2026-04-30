<?php

namespace App\Filament\Resources\SystemSettings\Schemas;

use App\Services\SystemSettingRules;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SystemSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->options(collect(SystemSettingRules::catalog())->mapWithKeys(
                        fn (array $row, string $key): array => [$key => "{$row['label']} ({$key})"]
                    )->all())
                    ->searchable()
                    ->required(),
                TextInput::make('group')->disabled()->dehydrated()->default('profit'),
                TextInput::make('value')
                    ->required()
                    ->helperText('Validation is enforced by key (min/max/type).'),
                Select::make('value_type')
                    ->options([
                        'string' => 'string',
                        'int' => 'int',
                        'float' => 'float',
                        'bool' => 'bool',
                    ])
                    ->disabled()
                    ->dehydrated(),
            ]);
    }
}
