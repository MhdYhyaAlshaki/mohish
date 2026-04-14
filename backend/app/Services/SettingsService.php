<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;

class SettingsService
{
    private ?Collection $cache = null;

    public function getString(string $key, ?string $default = null): ?string
    {
        $setting = $this->settings()->get($key);
        return $setting?->value ?? $default;
    }

    public function getFloat(string $key, float $default = 0): float
    {
        $value = $this->getString($key);
        return is_numeric($value) ? (float) $value : $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) round($this->getFloat($key, $default));
    }

    public function set(string $key, mixed $value, string $group = 'general', ?int $updatedBy = null): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => (string) $value,
                'value_type' => $this->inferType($value),
                'updated_by' => $updatedBy,
            ],
        );

        $this->cache = null;
    }

    private function settings(): Collection
    {
        if ($this->cache === null) {
            $this->cache = SystemSetting::query()->get()->keyBy('key');
        }

        return $this->cache;
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            default => 'string',
        };
    }
}
