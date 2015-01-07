<?php

abstract class ExternalGateway extends CComponent{
    
    protected $currencyId;
    protected $account;
    
    protected $paymentType;
    
    public function transferTo($address, $count) {}
    public function transferFrom($address, $count) {}
    public function getBalance($address) {}
    
    public function getType() {
        return $this->currencyId;
    }
}