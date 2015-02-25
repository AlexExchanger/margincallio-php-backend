<?php

class BitcoinController extends CController
{
    
    public function filters() {
        //return ['postOnly'];
    }

    public function actionReceived()
    {
        $salt = 'salt';
        try {
            if (!Service::checkRequest($salt)) {
                throw new BitcoinDaemonException('Wrong request sign. Request: '.json_encode($_POST));
            }

            $request = $_POST['request'];
            
            $address = ArrayHelper::getFromArray($request, 'address');
            $amount = ArrayHelper::getFromArray($request, 'amount');
            $txid = ArrayHelper::getFromArray($request, 'txid');

            $coinAddress = CoinAddress::getByAddress($address);
            if (!$coinAddress) {
                throw new BitcoinDaemonException('CoinAddress not found. Request: '.json_encode($_POST));
            }
            
            if(isset($coinAddress->lastTx) && $coinAddress->lastTx == $txid) {
                throw new BitcoinDaemonException('Transaction already done!. Request: '.json_encode($_POST));
            }
            
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        
        //transaction update
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            $transactionQuery = 'SELECT * FROM "transaction_external" WHERE "id"='.$coinAddress->transactionId." FOR UPDATE";
            $transaction = TransactionExternal::model()->findBySql($transactionQuery);
            if(!$transaction) {
                throw new SystemException('Transaction with id '.$coinAddress->transactionId.' doesn\'t exist!');
            }

            if($transaction->verifyStatus != 'pending') {
                
                $newTransaction = new TransactionExternal();
                $newTransaction->createdAt = TIME;
                $newTransaction->currency = 'BTC';
                $newTransaction->gatewayId = 2;
                $newTransaction->type = false;
                $newTransaction->verifyStatus = 'done';
                $newTransaction->accountId = $coinAddress->accountId;
                $newTransaction->amount = $amount;
                $newTransaction->details = json_encode(array(
                    'txid' => $txid,
                    'address' => $address
                ));
                
                if(!$newTransaction->save()) {
                    throw new SystemException('Error with additional transaction created!');
                }
                
            } else {
                $transaction->amount = $amount;
                $transaction->verifyStatus = 'done';
                $transaction->details = json_encode(array(
                    'txid' => $txid,
                    'address' => $address
                ));
                
            }
            
            $transaction->update();   
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError();
        }
        
        //account update
        $dbTransaction = Yii::app()->db->beginTransaction();
        try {   
            $query = 'SELECT * FROM "account" WHERE "id"='.$coinAddress->accountId." FOR UPDATE";
            $account = Account::model()->findBySql($query);
            if (!$account || $account->currency != 'BTC') {
                throw new SystemException('Account is not correct', array('request' => $_POST));
            }
            $account->balance = bcadd($account->balance, $amount);
            $account->update();
            
            /*push for daemon*/
            
            $coinAddress->lastTx = $txid;
            $coinAddress->used = true;
            $coinAddress->update();
            
            $dbTransaction->commit();
        } catch(Exception $e) {
            $dbTransaction->rollback();
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }

    public function actionSent()
    {
        $request = json_decode($_POST['request'], true);
        $transactionOrders = \ArrayHelper::getFromArray($request, 'transactionOrders');
        $commission = \ArrayHelper::getFromArray($request, 'commission');
        $txid = \ArrayHelper::getFromArray($request, 'txid');

        $gateway = \Gateway::getForPayment('bitcoin', 'BTC');
        if (!Service::checkRequest($gateway->secureData['salt'])) {
            throw new \SystemException(_('Wrong sign of request'), ['request' => $_POST]);
        }

        $accountExternal = Account::getOrCreateForSystem('system.gateway.external', $gateway);
        $accountCommission = Account::getOrCreateForSystem('system.gateway.external.systemCommission', $gateway);

        $dbTransaction = Account::model()->dbConnection->beginTransaction();
        try {
            $transactionGroup = \Guid::generate();
            foreach ($transactionOrders as $id) {
                $transactionOrder = \TransactionOrder::get($id);
                $transactionOrder->status = 'completed';
                $transactionOrder->finishedAt = TIME;
                $details = $transactionOrder->details;
                $details['details']['txid'] = $txid;
                $transactionOrder->details = $details;
                $transactionOrder->update(['status', 'finishedAt', 'details']);

                if ($transactionOrder->parentId) {
                    $firstTransactionOrder = \TransactionOrder::get($transactionOrder->parentId);
                    $details = $firstTransactionOrder->details;
                    $details['details']['txid'] = $txid;
                    $firstTransactionOrder->details = $details;
                    $firstTransactionOrder->update(['details']);
                }

                $accountFrom = Account::get($transactionOrder->accountFromId);
                $accountTo = Account::get($transactionOrder->accountToId);

                $transaction1 = new \Transaction();
                $transaction1->accountId = $accountFrom->id;
                $transaction1->debit = 0;
                $transaction1->credit = $transactionOrder->amount;
                $transaction1->createdAt = TIME;
                $transaction1->groupId = $transactionGroup;
                $transaction1->transactionOrderId = $transactionOrder->id;
                if (!$transaction1->save()) {
                    throw new \SystemException(_('Transaction was not saved'), $transaction1->getErrors());
                }

                $transaction2 = new \Transaction();
                $transaction2->accountId = $accountTo->id;
                $transaction2->debit = $transactionOrder->amount;
                $transaction2->credit = 0;
                $transaction2->createdAt = TIME;
                $transaction2->groupId = $transactionGroup;
                $transaction2->transactionOrderId = $transactionOrder->id;
                if (!$transaction2->save()) {
                    throw new \SystemException(_('Transaction was not saved'), $transaction2->getErrors());
                }

                $accountFrom->saveCounters(['balance' => "-$transactionOrder->amount"]);
                $accountTo->saveCounters(['balance' => $transactionOrder->amount]);

                $originalAccount = Account::get($transactionOrder->details['originalAccountId']);
                if ($originalAccount) {
                    \UserLog::addAction($originalAccount->userId, 'fundsWithdrawal', [
                        'accountId' => $originalAccount->publicId,
                        'currency' => $originalAccount->currency,
                        'amount' => $transactionOrder->amount
                    ]);
                }
            }


            $transaction1 = new \Transaction();
            $transaction1->accountId = $accountExternal->id;
            $transaction1->debit = 0;
            $transaction1->credit = $commission;
            $transaction1->createdAt = TIME;
            $transaction1->groupId = $transactionGroup;
            $transaction1->transactionOrderId = null;
            if (!$transaction1->save()) {
                throw new \SystemException(_('Transaction was not saved'), $transaction1->getErrors());
            }

            $transaction2 = new \Transaction();
            $transaction2->accountId = $accountCommission->id;
            $transaction2->debit = $commission;
            $transaction2->credit = 0;
            $transaction2->createdAt = TIME;
            $transaction2->groupId = $transactionGroup;
            $transaction2->transactionOrderId = null;
            if (!$transaction2->save()) {
                throw new \SystemException(_('Transaction was not saved'), $transaction2->getErrors());
            }

            $accountExternal->saveCounters(['balance' => "-$commission"]);
            $accountCommission->saveCounters(['balance' => $commission]);

            $dbTransaction->commit();
        }
        catch (\Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }

        $this->json();
    }
}