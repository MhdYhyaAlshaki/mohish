<?php

namespace App\Filament\Resources\SystemSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SystemSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->copyable(),
                TextColumn::make('group')->badge(),
                TextColumn::make('value')->searchable(),
                TextColumn::make('value_type')->badge(),
                TextColumn::make('updated_at')->since(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'profit' => 'profit',
                        'general' => 'general',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
