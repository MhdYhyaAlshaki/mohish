<?php

namespace App\Filament\Resources\PayoutTiers;

use App\Filament\Resources\PayoutTiers\Pages\CreatePayoutTier;
use App\Filament\Resources\PayoutTiers\Pages\EditPayoutTier;
use App\Filament\Resources\PayoutTiers\Pages\ListPayoutTiers;
use App\Filament\Resources\PayoutTiers\Schemas\PayoutTierForm;
use App\Filament\Resources\PayoutTiers\Tables\PayoutTiersTable;
use App\Models\PayoutTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayoutTierResource extends Resource
{
    protected static ?string $model = PayoutTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Profit Controls';
    protected static ?string $navigationLabel = 'Payout Tiers';

    public static function form(Schema $schema): Schema
    {
        return PayoutTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayoutTiersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayoutTiers::route('/'),
            'create' => CreatePayoutTier::route('/create'),
            'edit' => EditPayoutTier::route('/{record}/edit'),
        ];
    }
}
