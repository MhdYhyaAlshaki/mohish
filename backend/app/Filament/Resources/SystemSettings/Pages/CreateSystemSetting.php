<?php

namespace App\Filament\Resources\SystemSettings\Pages;

use App\Filament\Resources\SystemSettings\SystemSettingResource;
use App\Models\AdminAuditLog;
use App\Services\SystemSettingRules;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemSetting extends CreateRecord
{
    protected static string $resource = SystemSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $validated = SystemSettingRules::validateValue($data['key'], $data['value']);
        $data['value'] = $validated['value'];
        $data['value_type'] = $validated['value_type'];
        $data['group'] = $validated['group'];
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
