<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentSetting;

class PaymentGatewayFactory
{
    public static function make(?PaymentSetting $setting): PaymentGatewayInterface
    {
        if (!$setting || !$setting->is_active) {
            return new NullGateway();
        }

        if (app()->environment('testing') || (isset($setting->credentials['access_token']) && str_starts_with($setting->credentials['access_token'], 'TEST-'))) {
            return new NullGateway();
        }

        return match ($setting->gateway) {
            'mercadopago' => new MercadoPagoGateway($setting),
            default => new NullGateway(),
        };
    }
}
