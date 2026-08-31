<?php

namespace App\Support;

class PhoneHelper
{
    /**
     * Normalize a phone number to international E.164 without leading plus
     * e.g. "(34) 99944-2627" -> "553499442627"
     */
    public static function normalize(?string $phone, string $defaultCountry = 'BR'): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters
        $digits = preg_replace('/\D+/', '', $phone);

        if (empty($digits)) {
            return null;
        }

        // If Brazil (default)
        if (strtoupper($defaultCountry) === 'BR') {
            // Already has country code 55 (e.g. 5534999442627 or 553499442627)
            if (str_starts_with($digits, '55') && (strlen($digits) === 12 || strlen($digits) === 13)) {
                return $digits;
            }

            // Local Brazilian number with DDD: 10 digits (fixed) or 11 digits (mobile with 9)
            if (strlen($digits) === 10 || strlen($digits) === 11) {
                return '55' . $digits;
            }
        }

        return $digits;
    }

    /**
     * Format a phone number for display with country code
     */
    public static function format(?string $phone): string
    {
        $normalized = self::normalize($phone);
        if (!$normalized) {
            return (string) $phone;
        }

        if (str_starts_with($normalized, '55') && strlen($normalized) === 13) {
            // +55 (34) 99944-2627
            return sprintf(
                '+%s (%s) %s-%s',
                substr($normalized, 0, 2),
                substr($normalized, 2, 2),
                substr($normalized, 4, 5),
                substr($normalized, 9, 4)
            );
        }

        if (str_starts_with($normalized, '55') && strlen($normalized) === 12) {
            // +55 (34) 9944-2627
            return sprintf(
                '+%s (%s) %s-%s',
                substr($normalized, 0, 2),
                substr($normalized, 2, 2),
                substr($normalized, 4, 4),
                substr($normalized, 8, 4)
            );
        }

        return '+' . $normalized;
    }

    /**
     * List of supported countries with their DDI and flags
     */
    public static function countryList(): array
    {
        return [
            ['code' => 'BR', 'name' => 'Brasil', 'ddi' => '+55', 'flag' => '🇧🇷'],
            ['code' => 'US', 'name' => 'Estados Unidos', 'ddi' => '+1', 'flag' => '🇺🇸'],
            ['code' => 'PT', 'name' => 'Portugal', 'ddi' => '+351', 'flag' => '🇵🇹'],
            ['code' => 'ES', 'name' => 'Espanha', 'ddi' => '+34', 'flag' => '🇪🇸'],
            ['code' => 'AR', 'name' => 'Argentina', 'ddi' => '+54', 'flag' => '🇦🇷'],
            ['code' => 'UY', 'name' => 'Uruguai', 'ddi' => '+598', 'flag' => '🇺🇾'],
            ['code' => 'PY', 'name' => 'Paraguai', 'ddi' => '+595', 'flag' => '🇵🇾'],
            ['code' => 'CL', 'name' => 'Chile', 'ddi' => '+56', 'flag' => '🇨🇱'],
            ['code' => 'FR', 'name' => 'França', 'ddi' => '+33', 'flag' => '🇫🇷'],
            ['code' => 'IT', 'name' => 'Itália', 'ddi' => '+39', 'flag' => '🇮🇹'],
            ['code' => 'DE', 'name' => 'Alemanha', 'ddi' => '+49', 'flag' => '🇩🇪'],
            ['code' => 'GB', 'name' => 'Reino Unido', 'ddi' => '+44', 'flag' => '🇬🇧'],
        ];
    }
}
