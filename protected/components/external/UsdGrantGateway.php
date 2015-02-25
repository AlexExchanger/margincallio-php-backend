<?php

class UsdGrantGateway extends ExternalGateway {
    
    protected static $gatewayId = 4;

    public static function getInstance() {
        return self::model('UsdGrantGateway')->findByPk(self::$gatewayId);
    }

    public static function getBillingMeta($payment, $data) {
        return $payment;
    }
    
    public function transferFrom($accountId, $amount) {
        parent::transferFrom($accountId, $amount);
        
        $account = Account::get($accountId);
        if(!$account || $account->currency != 'USD') {
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
            $transaction->verifyStatus = 'pending';
            $transaction->accountId = $accountId;
            $transaction->amount = $amount;
            $transaction->createdAt = TIME;
            $transaction->currency = 'USD';
            
            if(!$transaction->save()) {
                throw new Exception();
            }
            
            $gatewayAccount = Account::model()->findByAttributes(array(
                'gateway' => "".self::$gatewayId,
                'currency' => 'USD'
            ));
            
            if(!$gatewayAccount) {
                throw new Exception();
            }
            
            $gatewayAccount->balance = bcadd($gatewayAccount->balance, $amount);
            if(!$gatewayAccount->save()) {
                return new Exception();
            }
            $dbTransaction->commit();
        } catch (Exception $e) {
            $dbTransaction->rollback();
            return false;
        }
        
        return true;
    }

    public function transferTo($accountId, $amount) {
        parent::transferFrom($accountId, $amount);
        
        $account = Account::get($accountId);
        if(!$account || $account->currency != 'USD') {
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
            $transaction->verifyStatus = 'pending';
            $transaction->accountId = $accountId;
            $transaction->amount = $amount;
            $transaction->createdAt = TIME;
            $transaction->currency = 'USD';
            
            if(!$transaction->save()) {
                throw new Exception();
            }
            
            $gatewayAccount = Account::model()->findByAttributes(array(
                'gateway' => "".self::$gatewayId,
                'currency' => 'USD'
            ));
            
            if(!$gatewayAccount) {
                throw new Exception();
            }
            
            $gatewayAccount->balance = bcsub($gatewayAccount->balance, $amount);
            if(!$gatewayAccount->save()) {
                return new Exception();
            }
            $dbTransaction->commit();
        } catch (Exception $e) {
            $dbTransaction->rollback();
            return false;
        }
        
        return true;
    }
    
}