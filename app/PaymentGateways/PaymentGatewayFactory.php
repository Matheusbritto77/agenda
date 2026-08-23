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

        return match ($setting->gateway) {
            'mercadopago' => new MercadoPagoGateway($setting),
            default => new NullGateway(),
        };
    }
}
