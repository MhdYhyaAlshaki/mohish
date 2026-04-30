<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class SystemSettingRules
{
    /**
     * @return array<string, array{type:string,min?:float,max?:float,group:string,label:string}>
     */
    public static function catalog(): array
    {
        return [
            'revenue_per_1000_ads' => ['type' => 'float', 'min' => 0.01, 'max' => 1000, 'group' => 'profit', 'label' => 'Revenue per 1000 Ads'],
            'base_user_payout_points_per_ad' => ['type' => 'int', 'min' => 1, 'max' => 10000, 'group' => 'profit', 'label' => 'Base User Payout Points per Ad'],
            'point_cash_value' => ['type' => 'float', 'min' => 0.000001, 'max' => 10, 'group' => 'profit', 'label' => 'Point Cash Value'],
            'referral_percent' => ['type' => 'int', 'min' => 0, 'max' => 100, 'group' => 'profit', 'label' => 'Referral Percent'],
            'daily_cap' => ['type' => 'int', 'min' => 1, 'max' => 500, 'group' => 'limits', 'label' => 'Daily Ad Cap'],
            'cooldown_seconds' => ['type' => 'int', 'min' => 0, 'max' => 3600, 'group' => 'limits', 'label' => 'Cooldown Seconds'],
            'session_expiry_minutes' => ['type' => 'int', 'min' => 1, 'max' => 1440, 'group' => 'limits', 'label' => 'Session Expiry Minutes'],
            'daily_claim_points' => ['type' => 'int', 'min' => 0, 'max' => 10000, 'group' => 'limits', 'label' => 'Daily Claim Points'],
            'min_withdraw_points' => ['type' => 'int', 'min' => 1, 'max' => 100000000, 'group' => 'limits', 'label' => 'Minimum Withdraw Points'],
            'ads_enabled' => ['type' => 'bool', 'group' => 'flags', 'label' => 'Ads Enabled'],
            'feature_flag_rewarded_ads' => ['type' => 'bool', 'group' => 'flags', 'label' => 'Rewarded Ads Enabled'],
            'feature_flag_interstitial_ads' => ['type' => 'bool', 'group' => 'flags', 'label' => 'Interstitial Ads Enabled'],
            'feature_flag_app_open_ads' => ['type' => 'bool', 'group' => 'flags', 'label' => 'App Open Ads Enabled'],
        ];
    }

    public static function validateValue(string $key, mixed $value): array
    {
        $rules = self::catalog()[$key] ?? null;
        if (! $rules) {
            throw ValidationException::withMessages([
                'key' => "Unsupported setting key: {$key}",
            ]);
        }

        return match ($rules['type']) {
            'bool' => self::validateBool($value, $rules),
            'int' => self::validateNumeric($value, $rules, true),
            'float' => self::validateNumeric($value, $rules, false),
            default => throw ValidationException::withMessages(['value' => 'Invalid setting type']),
        };
    }

    /**
     * @param array{type:string,min?:float,max?:float,group:string,label:string} $rules
     */
    private static function validateNumeric(mixed $value, array $rules, bool $isInt): array
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages(['value' => 'Value must be numeric']);
        }

        $number = $isInt ? (float) (int) $value : (float) $value;
        $min = $rules['min'] ?? null;
        $max = $rules['max'] ?? null;
        if ($min !== null && $number < $min) {
            throw ValidationException::withMessages(['value' => "Value must be >= {$min}"]);
        }
        if ($max !== null && $number > $max) {
            throw ValidationException::withMessages(['value' => "Value must be <= {$max}"]);
        }

        return [
            'value' => $isInt ? (string) (int) $number : (string) $number,
            'value_type' => $isInt ? 'int' : 'float',
            'group' => $rules['group'],
        ];
    }

    /**
     * @param array{type:string,min?:float,max?:float,group:string,label:string} $rules
     */
    private static function validateBool(mixed $value, array $rules): array
    {
        $normalized = strtolower(trim((string) $value));
        $allowed = ['1', '0', 'true', 'false', 'yes', 'no'];
        if (! in_array($normalized, $allowed, true)) {
            throw ValidationException::withMessages(['value' => 'Value must be boolean: true/false']);
        }

        $boolValue = in_array($normalized, ['1', 'true', 'yes'], true);

        return [
            'value' => $boolValue ? 'true' : 'false',
            'value_type' => 'bool',
            'group' => $rules['group'],
        ];
    }
}
