<?php

abstract class ExternalGateway extends CComponent {
    
    private $currencyId;
    private $account;
    private $remoteAccount;
    
    public function transferTo($address, $count);
    public function transferFrom($address, $count);
    public function getBalance($address);
    
    public function getType() {
        return $this->currencyId;
    }
    
}
