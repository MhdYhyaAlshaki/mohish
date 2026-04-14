<?php

namespace App\Filament\Resources\PayoutTiers\Pages;

use App\Filament\Resources\PayoutTiers\PayoutTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayoutTier extends EditRecord
{
    protected static string $resource = PayoutTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
