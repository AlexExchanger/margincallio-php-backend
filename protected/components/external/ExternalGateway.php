<?php

abstract class ExternalGateway extends CComponent {
    
    private $currencyId;
    private $account;
    private $remoteAccount;
    
    public function transferTo($count);
    public function transferFrom($count);
    public function getBalance($address);
    
    public function getType() {
        return $this->currencyId;
    }
    
}
