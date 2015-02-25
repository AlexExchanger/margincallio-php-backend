<?php

class UsdBoaGateway extends ExternalGateway {
    
    protected static $gatewayId = 3;

    public static function getInstance() {
        return self::model('UsdBoaGateway')->findByPk(self::$gatewayId);
    }

    public static function getBillingMeta($payment, $data) {
        return $payment;
    }
    
}