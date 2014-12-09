<?php

class BitcoinController extends ApiController
{

    public function filters()
    {
        return ['postOnly'];
    }


    public function actionReceived()
    {
        $request = json_decode($_POST['request'], true);
        $address = ArrayHelper::getFromArray($request, 'address');
        $amount = ArrayHelper::getFromArray($request, 'amount');
        $txid = ArrayHelper::getFromArray($request, 'txid');


        $coinAddress = CoinAddress::getByAddress($address);
        if (!$coinAddress) {
            throw new MemcachedException('CoinAddress not found', array('request' => $_POST));
        }

        $gateway = Gateway::get($coinAddress->gatewayId);
        if (!Service::checkRequest($gateway->secureData['salt'])) {
            throw new SystemException('Wrong request sign', array('request' => $_POST));
        }

        $blockChain = @file_get_contents("https://blockchain.info/rawtx/$txid");
        if (empty($blockChain)) {
            throw new SystemException('Got empty data from blockchain.com', array('request' => $_POST));
        }
        
        $blockChain = json_decode($blockChain, true);
        if (!is_array($blockChain) || empty($blockChain['out'])) {
            throw new SystemException('Got empty data from blockchain.com (empty [out])', array('request' => $_POST, 'blockchain' => $blockChain));
        }

        $isFound = false;
        foreach ($blockChain['out'] as $tr) {
            if ($tr['addr'] == $address && bccomp(bcmul($amount, '100000000'), $tr['value']) == 0) {
                $isFound = true;
                break;
            }
        }

        if (!$isFound) {
            throw new SystemException('Order not found', array('request' => $_POST, 'blockchain' => $blockChain));
        }


        $account = Account::get($coinAddress->accountId);
        // todo type check
        if (!$account || $account->currency != 'BTC') {
            throw new SystemException('Account is not correct', array('request' => $_POST));
        }

        $gatewaySearchHash = md5("$txid:$address:$amount");

        $transactionOrder = TransactionOrder::getByGatewaySearchHashForUpdate($gateway, $gatewaySearchHash);
        if ($transactionOrder) {
            $this->json();
        }

        $accountUniverse = Account::getOrCreateForSystem('system.gateway.external.universe', $gateway);
        $accountExternal = Account::getOrCreateForSystem('system.gateway.external', $gateway);
        $accountInternal = Account::getOrCreateForSystem('system.gateway.internal', $gateway);
        $accountCommission = Account::getOrCreateForSystem('system.gateway.internal.commission', $gateway);

        $transactionGroup = Guid::generate();

        // создаем завершенный transactionOrder чтобы не зачислить деньги дважды
        $transactionOrder = new TransactionOrder();
        $transactionOrder->status = 'completed';
        $transactionOrder->gatewayId = $gateway->id;
        $transactionOrder->accountFromId = $accountUniverse->id;
        $transactionOrder->accountFromType = $accountUniverse->type;
        $transactionOrder->accountToId = $accountExternal->id;
        $transactionOrder->accountToType = $accountExternal->type;
        $transactionOrder->createdAt = TIME;
        $transactionOrder->createdBy = $account->userId;
        $transactionOrder->currency = $gateway->currency;
        $transactionOrder->amount = $amount;
        $transactionOrder->gatewaySearchHash = $gatewaySearchHash;
        $transactionOrder->comment = '';
        $transactionOrder->transactionGroupId = $transactionGroup;
        $transactionOrder->details = [
            'details' => [
                'txid' => $txid,
                'address' => $address
            ]
        ];

        $transaction1 = new Transaction();
        $transaction1->accountId = $accountUniverse->id;
        $transaction1->debit = 0;
        $transaction1->credit = $amount;
        $transaction1->createdAt = TIME;
        $transaction1->groupId = $transactionGroup;
        $transaction1->transactionOrderId = $transactionOrder->id;
        if (!$transaction1->save()) {
            throw new SystemException('Transaction was not saved', $transaction1->getErrors());
        }

        $transaction2 = new Transaction();
        $transaction2->accountId = $accountExternal->id;
        $transaction2->debit = $amount;
        $transaction2->credit = 0;
        $transaction2->createdAt = TIME;
        $transaction2->groupId = $transactionGroup;
        $transaction2->transactionOrderId = $transactionOrder->id;
        if (!$transaction2->save()) {
            throw new SystemException(_('Transaction was not saved'), $transaction2->getErrors());
        }

        $accountUniverse->saveCounters(['balance' => "-$amount"]);
        $accountExternal->saveCounters(['balance' => $amount]);

        $commission = Commission::get('gateway.addFunds', $gateway);

        $newTransactionOrder = new TransactionOrder();
        $newTransactionOrder->status = 'waitForAccountant';
        $newTransactionOrder->gatewayId = $gateway->id;
        $newTransactionOrder->accountFromId = $accountInternal->id;
        $newTransactionOrder->accountFromType = $accountInternal->type;
        $newTransactionOrder->accountToId = $account->id;
        $newTransactionOrder->accountToType = $account->type;
        $newTransactionOrder->createdAt = TIME;
        $newTransactionOrder->createdBy = $account->userId;
        $newTransactionOrder->currency = $gateway->currency;
        $newTransactionOrder->amount = $amount;
        $newTransactionOrder->gatewaySearchHash = null;
        $newTransactionOrder->comment = '';
        $newTransactionOrder->parentId = $transactionOrder->id;
        $newTransactionOrder->transactionGroupId = $transactionGroup;
        $newTransactionOrder->details = [
            'accountantConfirmed' => [],
            'commission' => [
                'accountId' => $accountCommission->id,
                'multiplier' => $commission ? $commission->rules['multiplier'] : '0',
                'max' => $commission ? $commission->rules['max'] : '0',
            ],
            'details' => [
                'txid' => $txid,
                'address' => $address
            ]
        ];
        if (!$newTransactionOrder->save()) {
            throw new SystemException('TransactionOrder was not created', $transactionOrder->getErrors());
        }
        $this->json();
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