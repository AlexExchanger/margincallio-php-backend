<?php

class UsdBankGateway extends ExternalGateway {
    
    protected static $gatewayId = 6;

    public static function getInstance() {
        return self::model('UsdBankGateway')->findByPk(self::$gatewayId);
    }

    public static function getBillingMeta($payment, $data) {
        
        $userId = Yii::app()->user->id;
        if(!isset($userId)) {
            return false;
        }
        
        return preg_replace_callback('/USERNAME_PLACEHOLDER/', function($matches) use ($userId){
            return $userId;
        }, $payment);
    }
    
    public function transferTo($accountId, $transactionId, $amount, $data) {
        
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
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
            
            $userId = Yii::app()->user->id;
            if(!isset($userId)) {
                throw new Exception();
            }
            
            $ticket = Ticket::create(array(
                'title' => 'Income',
                'department' => 'finance',      
            ), income('. ', $data), $userId);
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            return false;
        }
        
        return true;
    }
    
    public function transferFrom($accountId, $transactionId, $amount, $data) {
        $dbTransaction = Yii::app()->db->beginTransaction();
        
        try {
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
            
            $userId = Yii::app()->user->id;
            if(!isset($userId)) {
                throw new Exception();
            }
            
            $ticket = Ticket::create(array(
                'title' => 'Outcome',
                'department' => 'finance',      
            ), income('. ', $data), $userId);
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            return false;
        }
        
        return true;
    }
    
    
}