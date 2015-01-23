<?php

class UsdBoaGateway extends ExternalGateway {
    
    protected static $gatewayId = 3;

    public static function getInstance() {
        return self::model('UsdBoaGateway')->findByPk(self::$gatewayId);
    }

    public function getBillingMeta() {
        
    }
    
}