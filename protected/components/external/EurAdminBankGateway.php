<?php

class EurAdminBankGateway extends ExternalGateway {
    
    protected static $gatewayId = 7;

    public static function getInstance() {
        return self::model('EurAdminBankGateway')->findByPk(self::$gatewayId);
    }

    public static function getBillingMeta($payment, $data) {
        
    }
   
    
    public function transferTo($accountId, $transactionId, $amount, $data) {
        
    }
    
    public function transferFrom($accountId, $transactionId, $amount, $data) {
        
    }
    
    
}