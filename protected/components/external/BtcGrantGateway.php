<?php

class BtcGrantGateway extends ExternalGateway {
    
    protected static $gatewayId = 5;

    public static function getInstance() {
        return self::model('BtcGrantGateway')->findByPk(self::$gatewayId);
    }

    public function getBillingMeta() {
        
    }
    
    public function transferFrom($accountId, $amount) {
        parent::transferFrom($accountId, $amount);
        
        $account = Account::get($accountId);
        if(!$account || $account->currency != 'BTC') {
            return false;
        }
        
        if(bccomp($account->currency, $amount) < 0) {
            return false;
        }
        
        $dbTransaction = $account->dbConnection->beginTransaction();
        
        try {
            
            $account->balance = bcsub($account->balance, $amount); 
            
            if(!$account->save()) {
                throw new Exception();
            }
            
            $transaction = new TransactionExternal();
            $transaction->gatewayId = self::$gatewayId;
            $transaction->type = true;
            $transaction->status = 'pending';
            $transaction->accountId = $accountId;
            $transaction->amount = $amount;
            $transaction->createdAt = TIME;
            $transaction->currency = 'BTC';
            
            if(!$transaction->save()) {
                throw new Exception();
            }
            
            $gatewayAccount = Account::model()->findByAttributes(array(
                'gateway' => self::$gatewayId,
                'currency' => 'BTC'
            ));
            
            if(!$gatewayAccount) {
                throw new Exception();
            }
            
            $gatewayAccount->balance = bcadd($gatewayAccount->balance, $amount);
            if(!$gatewayAccount->save()) {
                return new Exception();
            }
            
        } catch (Exception $e) {
            $dbTransaction->rollback();
            return false;
        }
        
        return true;
    }

    public function transferTo($accountId, $amount) {
        parent::transferFrom($accountId, $amount);
        
        $account = Account::get($accountId);
        if(!$account || $account->currency != 'BTC') {
            return false;
        }
        
        $dbTransaction = $account->dbConnection->beginTransaction();
        
        try {
            
            $account->balance = bcadd($account->balance, $amount); 
            
            if(!$account->save()) {
                throw new Exception();
            }
            
            $transaction = new TransactionExternal();
            $transaction->gatewayId = self::$gatewayId;
            $transaction->type = false;
            $transaction->status = 'pending';
            $transaction->accountId = $accountId;
            $transaction->amount = $amount;
            $transaction->createdAt = TIME;
            $transaction->currency = 'BTC';
            
            if(!$transaction->save()) {
                throw new Exception();
            }
            
            $gatewayAccount = Account::model()->findByAttributes(array(
                'gateway' => self::$gatewayId,
                'currency' => 'BTC'
            ));
            
            if(!$gatewayAccount) {
                throw new Exception();
            }
            
            $gatewayAccount->balance = bcsub($gatewayAccount->balance, $amount);
            if(!$gatewayAccount->save()) {
                return new Exception();
            }
            
        } catch (Exception $e) {
            $dbTransaction->rollback();
            return false;
        }
        
        return true;
    }
    
}