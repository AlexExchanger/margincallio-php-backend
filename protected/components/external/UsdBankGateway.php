<?php

class UsdBankGateway extends ExternalGateway {
    
    protected static $gatewayId = 6;

    public static function getInstance() {
        return self::model('UsdBankGateway')->findByPk(self::$gatewayId);
    }

    public static function getBillingMeta($payment, $data) {
        return $payment;
    }
    
}