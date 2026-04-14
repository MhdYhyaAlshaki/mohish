<?php

namespace App\Filament\Resources\SystemSettings\Pages;

use App\Filament\Resources\SystemSettings\SystemSettingResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemSetting extends CreateRecord
{
    protected static string $resource = SystemSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        AdminAuditLog::query()->create([
            'admin_user_id' => auth()->id(),
            'action' => 'setting_created',
            'target_type' => 'system_setting',
            'target_id' => $this->record->id,
            'meta' => ['key' => $this->record->key],
            'ip_address' => request()->ip(),
        ]);
    }
}
