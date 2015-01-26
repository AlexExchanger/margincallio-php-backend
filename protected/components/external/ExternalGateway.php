<?php

class ExternalGateway extends CActiveRecord{
    
    public function tableName() {
        return 'gateway';
    }
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    public static function processPayment($data, $payment) {
        $gateway = GatewayFactory::create($data['gatewayId']);
        if(!$gateway) {
            return false;
        }
        
        $paymentFormJSON = $gateway->payment;
        $paymentForm = json_decode($paymentFormJSON, true);
        $userForm = json_decode($payment, true);
       
        foreach($paymentForm as $group) {
            foreach($group['fields'] as $field) {
                if($field['required'] == true) {
                    if(!isset($userForm[$field['name']])) {
                        return false;
                    }
                } else {
                    if(!isset($userForm[$field['name']])) {
                        continue;
                    }
                }
                
                switch($field['type']) {
                    case 'String':
                        if(!is_string($userForm[$field['name']])) {
                            return false;
                        }
                        break;
                    case 'Checkbox':
                        if(!is_bool($userForm[$field['name']])) {
                            return false;
                        }
                        break;
                }
            }
        }
        
        $transaction = new TransactionExternal();
        $transaction->gatewayId = $data['gatewayId'];
        $transaction->accountId = $data['accountId'];
        $transaction->amount = $data['amount'];
        $transaction->createdAt = TIME;
        $transaction->currency = $data['currency'];
        $transaction->details = json_encode($userForm);
        
        return $transaction->save();
    }
    
    public function getBillingMeta() {}
    public function transferFrom() {}
    public function transferTo() {}
}