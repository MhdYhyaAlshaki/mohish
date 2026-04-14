<?php

namespace App\Filament\Resources\PayoutTiers\Pages;

use App\Filament\Resources\PayoutTiers\PayoutTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayoutTiers extends ListRecords
{
    protected static string $resource = PayoutTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
