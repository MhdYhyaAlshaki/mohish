<?php

namespace App\Filament\Resources\SystemSettings\Pages;

use App\Filament\Resources\SystemSettings\SystemSettingResource;
use App\Models\AdminAuditLog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSystemSetting extends EditRecord
{
    protected static string $resource = SystemSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function afterSave(): void
    {
        AdminAuditLog::query()->create([
            'admin_user_id' => auth()->id(),
            'action' => 'setting_updated',
            'target_type' => 'system_setting',
            'target_id' => $this->record->id,
            'meta' => ['key' => $this->record->key, 'value' => $this->record->value],
            'ip_address' => request()->ip(),
        ]);
    }
}
