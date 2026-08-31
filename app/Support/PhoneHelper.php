<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Throwable;

class PhoneHelper
{
    /**
     * Normalize a phone number to international E.164 without leading plus
     * using Google's official libphonenumber.
     * e.g. "(34) 99944-2627" (BR) -> "5534999442627"
     */
    public static function normalize(?string $phone, string $defaultCountry = 'BR'): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $defaultRegion = strtoupper($defaultCountry ?: 'BR');

        try {
            $numberProto = $phoneUtil->parse($phone, $defaultRegion);

            if ($phoneUtil->isValidNumber($numberProto) || $phoneUtil->isPossibleNumber($numberProto)) {
                $e164 = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
                return ltrim($e164, '+');
            }
        } catch (NumberParseException $e) {
            // Segue para o fallback caso seja uma string com formatação incompleta
        } catch (Throwable $e) {
            // Ignore
        }

        // Fallback secundário
        $digits = preg_replace('/\D+/', '', $phone);
        if (empty($digits)) {
            return null;
        }

        if ($defaultRegion === 'BR') {
            if (str_starts_with($digits, '55') && (strlen($digits) === 12 || strlen($digits) === 13)) {
                return $digits;
            }
            if (strlen($digits) === 10 || strlen($digits) === 11) {
                return '55' . $digits;
            }
        }

        return $digits;
    }

    /**
     * Format a phone number for display using Google libphonenumber
     */
    public static function format(?string $phone, string $defaultCountry = 'BR', bool $international = true): string
    {
        if (empty($phone)) {
            return '';
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $defaultRegion = strtoupper($defaultCountry ?: 'BR');

        try {
            $numberProto = $phoneUtil->parse($phone, $defaultRegion);
            if ($phoneUtil->isValidNumber($numberProto) || $phoneUtil->isPossibleNumber($numberProto)) {
                $format = $international ? PhoneNumberFormat::INTERNATIONAL : PhoneNumberFormat::NATIONAL;
                return $phoneUtil->format($numberProto, $format);
            }
        } catch (Throwable $e) {
            // Retorna a string original em caso de erro
        }

        return (string) $phone;
    }

    /**
     * Validate if a phone number is valid using Google libphonenumber
     */
    public static function isValid(?string $phone, string $defaultCountry = 'BR'): bool
    {
        if (empty($phone)) {
            return false;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $defaultRegion = strtoupper($defaultCountry ?: 'BR');

        try {
            $numberProto = $phoneUtil->parse($phone, $defaultRegion);
            return $phoneUtil->isValidNumber($numberProto);
        } catch (Throwable) {
            return false;
        }
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
