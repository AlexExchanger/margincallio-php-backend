<?php


class TransactionOrder extends CActiveRecord
{
    public $accountFrom;
    public $accountTo;

    public static $statusOptions = ['waitForTreasurer', 'waitForAccountant', 'rejected', 'completed'];

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'transaction_order';
    }

    public function rules()
    {
        return array(
            array('status', 'in', 'allowEmpty' => false, 'range' => self::$statusOptions, 'strict' => true),
            array('currency', 'in', 'allowEmpty' => false, 'range' => \Account::$currencyOptions, 'strict' => true),
        );
    }

    public function beforeSave()
    {
        $this->details = json_encode($this->details, JSON_UNESCAPED_UNICODE);
        return parent::beforeSave();
    }

    public function afterSave()
    {
        parent::afterSave();
        $this->details = json_decode($this->details, true);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->details = json_decode($this->details, true);
    }


    public static function get($id)
    {
        return TransactionOrder::model()->findByPk($id);
    }


    public static function getByGatewaySearchHashForUpdate(Gateway $gateway, $hash)
    {
        $to = self::model()->dbConnection
            ->createCommand('select * from transaction_order where gatewayId = :g and gatewaySearchHash = :s limit 1 for update')
            ->queryRow(true, [':g' => $gateway->id, ':s' => $hash]);
        if ($to) {
            $to = self::model()->populateRecord($to, true);
        }
        return $to;
    }


    public static function getByGatewayCsv(GatewayCsv $gatewayCsv)
    {
        $ids = [];
        $transactionOrders = [];
        foreach ($gatewayCsv->transactionOrders as $data) {
            $ids[] = $data['id'];
        }
        if ($ids) {
            $transactionOrders = TransactionOrder::model()->findAllByPk($ids);
        }
        return $transactionOrders;
    }


    public static function createFromCsv(Gateway $gateway, GatewayCsv $csv, $userId)
    {
        $csvContent = GatewayCsv::getCsvContent($csv);

        $transactionOrdersCsv = [];

        $parser = new CsvParser($gateway->type);
        $rows = $parser->parse($csvContent);

        try {
            $accountUniverse = Account::getOrCreateForSystem('system.gateway.external.universe', $gateway);
            $accountExternal = Account::getOrCreateForSystem('system.gateway.external', $gateway);
            $accountUnknown = Account::getOrCreateForSystem('system.gateway.external.universe.unknown', $gateway);
            $accountInternal = Account::getOrCreateForSystem('system.gateway.internal', $gateway);
            $accountCommission = Account::getOrCreateForSystem('system.gateway.internal.commission', $gateway);

            foreach ($rows as $row) {
                //TODO случай если выписка не с того счета, может быть стоит выкинуть ошибку
                if ($row->bankAccountId !== $gateway->details['account']) {
                    continue;
                }

                //100% идентификация банковской транзакции
                $gatewaySearchHash = md5("$row->bankAccountId:$row->debit:$row->credit:$row->createdAt:$row->comment");

                $transactionOrder = self::getByGatewaySearchHashForUpdate($gateway, $gatewaySearchHash);


                if (!$transactionOrder) {

                    $accountUser = Account::get($row->accountId);

                    $transactionOrder = new TransactionOrder();
                    $transactionOrder->status = 'waitForTreasurer';
                    $transactionOrder->gatewayId = $gateway->id;
                    $transactionOrder->accountFromId = $accountUser ? $accountUniverse->id : $accountUnknown->id;
                    $transactionOrder->accountFromType = $accountUser ? $accountUniverse->type : $accountUnknown->type;
                    $transactionOrder->accountToId = $accountExternal->id;
                    $transactionOrder->accountToType = $accountExternal->type;
                    $transactionOrder->createdAt = TIME;
                    $transactionOrder->createdBy = $userId;
                    $transactionOrder->currency = $gateway->currency;
                    $transactionOrder->amount = bcsub($row->debit, $row->credit);
                    $transactionOrder->gatewaySearchHash = $gatewaySearchHash;
                    $transactionOrder->comment = $row->comment;
                    $transactionOrder->firstTreasurerId = $userId;
                    $transactionOrder->details = [
                        'gatewayCsvId' => [
                            $csv->id
                        ],
                        'treasurerConfirmed' => [
                            [
                                'id' => $userId,
                                'time' => TIME
                            ]
                        ],
                        'userAccountId' => $accountUser ? $accountUser->id : null,
                        'userAccountType' => $accountUser ? $accountUser->type : null,
                        'csvInfo' => [
                            'bankTransactionId' => $row->bankTransactionId,
                            'bankAccountId' => $row->bankAccountId,
                            'accountId' => $row->accountId,
                            'createdAt' => $row->createdAt,
                            'debit' => $row->debit,
                            'credit' => $row->credit,
                            'comment' => $row->comment
                        ]
                    ];
                    if (!$transactionOrder->save()) {
                        throw new ModelException($transactionOrder->getErrors());
                    }

                    $transactionOrdersCsv[] = [
                        'id' => $transactionOrder->id,
                        'isNew' => true,
                        'isConfirm' => false,
                        'isUnknown' => !$accountUser
                    ];
                }

                {
                    $transactionGroup = Guid::generate();

                    $isUnknown = $transactionOrder->accountFromId != $accountUniverse->id;

                    { // перевод денег на external gateway
                        $transaction1 = new Transaction();
                        $transaction1->accountId = $transactionOrder->accountFromId;
                        $transaction1->debit = 0;
                        $transaction1->credit = $transactionOrder->amount;
                        $transaction1->createdAt = TIME;
                        $transaction1->groupId = $transactionGroup;
                        $transaction1->transactionOrderId = $transactionOrder->id;
                        if (!$transaction1->save()) {
                            throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 1'), $transaction1->getErrors());
                        }

                        $transaction2 = new Transaction();
                        $transaction2->accountId = $transactionOrder->accountToId;
                        $transaction2->debit = $transactionOrder->amount;
                        $transaction2->credit = 0;
                        $transaction2->createdAt = TIME;
                        $transaction2->groupId = $transactionGroup;
                        $transaction2->transactionOrderId = $transactionOrder->id;
                        if (!$transaction2->save()) {
                            throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 2'), $transaction2->getErrors());
                        }

                        // фактический перевод на экстернал
                        if ($isUnknown) {
                            $accountUnknown->saveCounters(['balance' => "-$transactionOrder->amount"]);
                        } else {
                            $accountUniverse->saveCounters(['balance' => "-$transactionOrder->amount"]);
                        }
                        $accountExternal->saveCounters(['balance' => $transactionOrder->amount]);
                    }

                    // transactionOrder закрывается
                    $details = $transactionOrder->details;
                    $details['gatewayCsvId'][] = $csv->id;
                    $details['treasurerConfirmed'][] = ['id' => $userId, 'time' => TIME];
                    $transactionOrder->details = $details;
                    $transactionOrder->status = 'completed';
                    $transactionOrder->finishedAt = TIME;
                    $transactionOrder->finishedBy = $userId;
                    $transactionOrder->update(['status', 'details', 'finishedAt', 'finishedBy']);

                    if (!$isUnknown) {

                        $userAccount = Account::get($transactionOrder->details['userAccountId']);

                        $commission = Commission::get('gateway.addFunds', $gateway);

                        $newTransactionOrder = new TransactionOrder();
                        $newTransactionOrder->status = 'waitForAccountant';
                        $newTransactionOrder->gatewayId = $gateway->id;
                        $newTransactionOrder->accountFromId = $accountInternal->id;
                        $newTransactionOrder->accountFromType = $accountInternal->type;
                        $newTransactionOrder->accountToId = $transactionOrder->details['userAccountId'];
                        $newTransactionOrder->accountToType = $transactionOrder->details['userAccountType'];
                        $newTransactionOrder->createdAt = TIME;
                        $newTransactionOrder->createdBy = $userId;
                        $newTransactionOrder->currency = $gateway->currency;
                        $newTransactionOrder->amount = $transactionOrder->amount;
                        $newTransactionOrder->parentId = $transactionOrder->id;
                        $newTransactionOrder->comment = sprintf(
                            _('Пополнение счета %s на %s %s от %s'),
                            $transactionOrder->details['csvInfo']['accountId'],
                            $transactionOrder->amount,
                            $transactionOrder->currency,
                            date('Y-m-d', $transactionOrder->details['csvInfo']['createdAt'])
                        );
                        $newTransactionOrder->details = [
                            'accountantConfirmed' => [],
                            'commission' => [
                                'accountId' => $accountCommission->id,
                                'multiplier' => $commission ? $commission->rules['multiplier'] : '0',
                                'max' => $commission ? $commission->rules['max'] : '0'
                            ]
                        ];
                        if (!$newTransactionOrder->save()) {
                            throw new ModelException($transactionOrder->getErrors());
                        }
                    }

                    $transactionOrdersCsv[] = [
                        'id' => $transactionOrder->id,
                        'isNew' => false,
                        'isConfirm' => true,
                        'isUnknown' => $isUnknown
                    ];

                }
            }

            $csv->transactionOrders = $transactionOrdersCsv;
            $csv->update(['transactionOrders']);
        }
        catch (Exception $e) {
            throw $e;
        }

        return true;
    }

    public static function requestCashOut(Account $account, $amount, array $details, $userId)
    {
        if (!in_array($account->type, ['user.trading'])) {
            throw new ModelException(_('You can not withdrawal from this account type'));
        }
        $dbTransaction = self::model()->dbConnection->beginTransaction();

        $transactionGroup = Guid::generate();

        try {
            $accountFrom = Account::getForUpdate($account->id);
            if (bccomp($accountFrom->balance, $amount) === -1) {
                throw new ModelException(_('Not enough funds on your account'));
            }

            switch ($accountFrom->type) {
                case 'user.trading':
                    $accountWithdraw = Account::getOrCreateForUser($accountFrom->userId, 'user.withdrawTrading', $accountFrom->currency);
                    break;
                case 'user.merchant':
                    $accountWithdraw = Account::getOrCreateForUser($accountFrom->userId, 'user.withdrawMerchant', $accountFrom->currency);
                    break;
                case 'user.wallet':
                    $accountWithdraw = Account::getOrCreateForUser($accountFrom->userId, 'user.withdrawWallet', $accountFrom->currency);
                    break;
                default:
                    throw new ModelException(_('Wrong account type'));
            }


            switch ($accountFrom->currency) {
                case 'BTC':
                    $gateway = Gateway::getForPayment('bitcoin', 'BTC');
                    if (!$gateway) {
                        throw new ModelException(_('Gateway not found'));
                    }
                    $response = Service::sendRequest(
                        $gateway->details['url'],
                        ['action' => 'validateAddress', 'address' => $details['address']],
                        $gateway->secureData['salt']
                    );
                    if (!$response || !$response['success']) {
                        throw new ModelException(_('Internal error'));
                    }
                    if (!$response['isValid']) {
                        throw new ModelException(_('Bitcoin address is not valid'));
                    }
                    if (!preg_match('~^\d+(\.\d{1,8})?$~', $amount) || bccomp($amount, '0.0001') === -1) {
                        throw new ModelException(_('Amount should be not less then 0.0001 BTC'));
                    }

                    $commission = Commission::get('gateway.withdraw', $gateway);
                    $accountInternal = Account::getOrCreateForSystem('system.gateway.internal', $gateway);
                    $accountCommission = Account::getOrCreateForSystem('system.gateway.internal.commission', $gateway);

                    // перевод средств с user.trading на user.withdraw*
                    $transaction1 = new Transaction();
                    $transaction1->accountId = $accountFrom->id;
                    $transaction1->debit = 0;
                    $transaction1->credit = $amount;
                    $transaction1->createdAt = TIME;
                    $transaction1->groupId = $transactionGroup;
                    $transaction1->transactionOrderId = null;
                    $transaction1->orderId = null;
                    if (!$transaction1->save()) {
                        throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 1'), $transaction1->getErrors());
                    }

                    $transaction2 = new Transaction();
                    $transaction2->accountId = $accountWithdraw->id;
                    $transaction2->debit = $amount;
                    $transaction2->credit = 0;
                    $transaction2->createdAt = TIME;
                    $transaction2->groupId = $transactionGroup;
                    $transaction2->transactionOrderId = null;
                    $transaction2->orderId = null;
                    if (!$transaction2->save()) {
                        throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 2'), $transaction1->getErrors());
                    }

                    $accountFrom->saveCounters(['balance' => "-$amount"]);
                    $accountWithdraw->saveCounters(['balance' => $amount]);

                    $transactionOrder = new self();
                    $transactionOrder->status = 'waitForAccountant';
                    $transactionOrder->gatewayId = $gateway->id;
                    $transactionOrder->accountFromId = $accountWithdraw->id;
                    $transactionOrder->accountFromType = $accountWithdraw->type;
                    $transactionOrder->accountToId = $accountInternal->id;
                    $transactionOrder->accountToType = $accountInternal->type;
                    $transactionOrder->createdAt = TIME;
                    $transactionOrder->createdBy = $userId;
                    $transactionOrder->currency = $gateway->currency;
                    $transactionOrder->amount = $amount;
                    $transactionOrder->details = [
                        'accountantConfirmed' => [],
                        'commission' => [
                            'accountId' => $accountCommission->id,
                            'multiplier' => $commission ? $commission->rules['multiplier'] : '0',
                            'max' => $commission ? $commission->rules['max'] : '0',
                        ],
                        'originalAccountId' => $accountFrom->id,
                        'details' => [
                            'address' => $details['address']
                        ]
                    ];
                    $transactionOrder->transactionGroupId = $transactionGroup;
                    if (!$transactionOrder->save()) {
                        throw new ModelException(_('Error with creating transaction order'), $transactionOrder->getErrors());
                    }
                    break;

                case 'USD':
                    $gateway = Gateway::getForPayment('bank.norvik', 'USD');
                    if (!$gateway) {
                        throw new ModelException(_('Gateway not found'));
                    }

                    $commission = Commission::get('gateway.withdraw', $gateway);
                    $accountInternal = Account::getOrCreateForSystem('system.gateway.internal', $gateway);
                    $accountCommission = Account::getOrCreateForSystem('system.gateway.internal.commission', $gateway);

                    // перевод средств с user.trading на user.withdraw*
                    $transaction1 = new Transaction();
                    $transaction1->accountId = $accountFrom->id;
                    $transaction1->debit = 0;
                    $transaction1->credit = $amount;
                    $transaction1->createdAt = TIME;
                    $transaction1->groupId = $transactionGroup;
                    $transaction1->transactionOrderId = null;
                    $transaction1->orderId = null;
                    if (!$transaction1->save()) {
                        throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 1'), $transaction1->getErrors());
                    }

                    $transaction2 = new Transaction();
                    $transaction2->accountId = $accountWithdraw->id;
                    $transaction2->debit = $amount;
                    $transaction2->credit = 0;
                    $transaction2->createdAt = TIME;
                    $transaction2->groupId = $transactionGroup;
                    $transaction2->transactionOrderId = null;
                    $transaction2->orderId = null;
                    if (!$transaction2->save()) {
                        throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 2'), $transaction1->getErrors());
                    }

                    $accountFrom->saveCounters(['balance' => "-$amount"]);
                    $accountWithdraw->saveCounters(['balance' => $amount]);

                    $transactionOrder = new self();
                    $transactionOrder->status = 'waitForAccountant';
                    $transactionOrder->gatewayId = $gateway->id;
                    $transactionOrder->accountFromId = $accountWithdraw->id;
                    $transactionOrder->accountFromType = $accountWithdraw->type;
                    $transactionOrder->accountToId = $accountInternal->id;
                    $transactionOrder->accountToType = $accountInternal->type;
                    $transactionOrder->createdAt = TIME;
                    $transactionOrder->createdBy = $userId;
                    $transactionOrder->currency = $gateway->currency;
                    $transactionOrder->amount = $amount;
                    $transactionOrder->details = [
                        'accountantConfirmed' => [],
                        'commission' => [
                            'accountId' => $accountCommission->id,
                            'multiplier' => $commission ? $commission->rules['multiplier'] : '0',
                            'max' => $commission ? $commission->rules['max'] : '0',
                        ],
                        'originalAccountId' => $accountFrom->id,
                        'details' => [
                            $details,
                        ]
                    ];
                    $transactionOrder->transactionGroupId = $transactionGroup;
                    if (!$transactionOrder->save()) {
                        throw new ModelException(_('Error with creating transaction order'), $transactionOrder->getErrors());
                    }
                    break;

                default:
                    throw new ModelException(_('Behavior not known for this currency'));
            }

            $dbTransaction->commit();

            \UserLog::addAction($accountFrom->userId, 'fundsWithdrawalRequest', [
                'accountId' => $accountFrom->publicId,
                'currency' => $accountFrom->currency,
                'amount' => $amount
            ]);
            \Notify::updateAccountBalances([$accountFrom->id, $accountWithdraw->id]);
        }
        catch (Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }

        return true;
    }


    public static function complete(array $transactionOrders, $userId)
    {
        $return = [];

        foreach ($transactionOrders as $transactionOrder) {
            try {
                self::_completeOne($transactionOrder, $userId);
                $return[] = [
                    'id' => $transactionOrder->id,
                    'status' => $transactionOrder->status,
                ];
            }
            catch (\Exception $e) {
                throw $e;
            }
        }

        return $return;
    }


    public static function subsidy(\Gateway $gateway, \Account $account, $amount, $userId)
    {
        if ($gateway->type !== 'subsidy') {
            throw new \ModelException(_('Incorrect gateway type'));
        }
        if (bccomp('0', $amount) >= 0) {
            throw new \ModelException(_('wrong amount'));
        }
        if ($account->currency != $gateway->currency) {
            throw new \ModelException(_('Currency gateway and account not match'));
        }
        if ($account->type != 'user.trading') {
            throw new \ModelException(_('account must have user.trading type'));
        }

        //internal gateway account
        $internalAccount = \Account::getForSystem('system.gateway.internal', $gateway);

        $transactionOrder = new \TransactionOrder();
        $transactionOrder->status = 'waitForAccountant';
        $transactionOrder->gatewayId = $gateway->id;
        $transactionOrder->accountFromId = $internalAccount->id;
        $transactionOrder->accountFromType = $internalAccount->type;
        $transactionOrder->accountToId = $account->id;
        $transactionOrder->accountToType = $account->type;
        $transactionOrder->createdAt = TIME;
        $transactionOrder->createdBy = $userId;
        $transactionOrder->amount = $amount;
        $transactionOrder->gatewaySearchHash = null;
        $transactionOrder->currency = $account->currency;
        $transactionOrder->comment = "subsidy $amount $account->currency for $account->publicId";
        $transactionOrder->transactionGroupId = Guid::generate();
        $details = [
            'commission' => [
                'multiplier' => 0,
                'max' => 0,
            ]
        ];
        $transactionOrder->details = $details;
        if (!$transactionOrder->save()) {
            throw new \ModelException(_('Transaction order not saved'), $transactionOrder->getErrors());
        }
        return $transactionOrder;
    }


    private static function _completeOne(TransactionOrder $transactionOrder, $userId)
    {
        if ($transactionOrder->status !== 'waitForAccountant') {
            throw new ModelException(_('Status not allowed to accept'));
        }

        $details = $transactionOrder->details;
        $details['accountantConfirmed'][] = ['id' => $userId, 'time' => TIME];
        $transactionOrder->details = $details;


        if (is_null($transactionOrder->firstAccountantId)) {
            $transactionOrder->firstAccountantId = $userId;
            $transactionOrder->update(['details', 'firstAccountantId']);
        }


        $accountFrom = Account::get($transactionOrder->accountFromId);
        $accountTo = Account::get($transactionOrder->accountToId);

        // комиссия и комиссионный аккаунт
        if (empty($transactionOrder->gatewayId)) {
            throw new ModelException(_('Unknown gatewayId'));
        }
        $gateway = Gateway::get($transactionOrder->gatewayId);
        if (!$gateway) {
            throw new ModelException(_('Gateway not found'));
        }

        $moneyFromInternal = $transactionOrder->amount;
        $moneyToCommission = bcmul($transactionOrder->amount, $transactionOrder->details['commission']['multiplier']);
        // комиссия не может быть больше, чем указано в значении commission.max
        if (bccomp($moneyToCommission, $transactionOrder->details['commission']['max']) === 1) {
            $moneyToCommission = $transactionOrder->details['commission']['max'];
        }
        $moneyToAccount = bcsub($transactionOrder->amount, $moneyToCommission);

        $transactionGroup = Guid::generate();

        {
            $transaction1 = new Transaction();
            $transaction1->accountId = $transactionOrder->accountFromId;
            $transaction1->debit = 0;
            $transaction1->credit = $moneyFromInternal;
            $transaction1->createdAt = TIME;
            $transaction1->groupId = $transactionGroup;
            $transaction1->transactionOrderId = $transactionOrder->id;
            if (!$transaction1->save()) {
                throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 1'), $transaction1->getErrors());
            }

            $transaction2 = new Transaction();
            $transaction2->accountId = $transactionOrder->accountToId;
            $transaction2->debit = $moneyToAccount;
            $transaction2->credit = 0;
            $transaction2->createdAt = TIME;
            $transaction2->groupId = $transactionGroup;
            $transaction2->transactionOrderId = $transactionOrder->id;
            if (!$transaction2->save()) {
                throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 2'), $transaction2->getErrors());
            }

            // Зачисляем комиссию
            if (bccomp($moneyToCommission, '0') === 1) {
                $accountGatewayCommission = Account::get($transactionOrder->details['commission']['accountId']);
                if (!$accountGatewayCommission) {
                    throw new ModelException(_('Where is commission account, bro?!'));
                }

                $transaction3 = new Transaction();
                $transaction3->accountId = $accountGatewayCommission->id;
                $transaction3->debit = $moneyToCommission;
                $transaction3->credit = 0;
                $transaction3->createdAt = TIME;
                $transaction3->groupId = $transactionGroup;
                $transaction3->transactionOrderId = $transactionOrder->id;
                if (!$transaction3->save()) {
                    throw new ModelException(sprintf(_('%s isn\'t save'), 'Transaction 3'), $transaction3->getErrors());
                }

                $accountGatewayCommission->saveCounters(['balance' => $moneyToCommission]);
            }

            // фактический перевод на аккаунт пользователя и комиссии
            $accountFrom->saveCounters(['balance' => "-$moneyFromInternal"]);
            $accountTo->saveCounters(['balance' => $moneyToAccount]);
        }

        $transactionOrder->status = 'completed';
        $transactionOrder->finishedAt = TIME;
        $transactionOrder->finishedBy = $userId;

        $transactionOrder->update(['status', 'details', 'finishedAt', 'finishedBy', 'firstAccountantId']);


        if ($gateway->type == 'subsidy') {
            $accountGatewayUniverse = \Account::getForSystem('system.gateway.external.universe', $gateway);
            $accountGatewayExternal = \Account::getForSystem('system.gateway.external', $gateway);

            $transaction1 = new Transaction();
            $transaction1->accountId = $accountGatewayUniverse->id;
            $transaction1->debit = 0;
            $transaction1->credit = $transactionOrder->amount;
            $transaction1->createdAt = TIME;
            $transaction1->groupId = $transactionGroup;
            $transaction1->transactionOrderId = $transactionOrder->id;
            if (!$transaction1->save()) {
                throw new \ModelException(_('subsidy transaction1 not saved'), $transaction1->getErrors());
            }

            $transaction2 = new Transaction();
            $transaction2->accountId = $accountGatewayExternal->id;
            $transaction2->debit = $transactionOrder->amount;
            $transaction2->credit = 0;
            $transaction2->createdAt = TIME;
            $transaction2->groupId = $transactionGroup;
            $transaction2->transactionOrderId = $transactionOrder->id;
            if (!$transaction2->save()) {
                throw new \ModelException(_('subsidy transaction2 not saved'), $transaction2->getErrors());
            }

            $accountGatewayUniverse->saveCounters(['balance' => "-$transactionOrder->amount"]);
            $accountGatewayExternal->saveCounters(['balance' => $transactionOrder->amount]);
        }


        if ($accountFrom->type == 'system.gateway.internal') {
            \Notify::fundsAdded($accountTo->userId, $accountTo, $moneyToAccount);
            \UserLog::addAction($accountTo->userId, 'fundsAdded', [
                'accountId' => $accountTo->publicId,
                'currency' => $accountTo->currency,
                'amount' => $moneyToAccount
            ]);
        } elseif ($accountTo->type == 'system.gateway.internal') {
            $accountUniverse = Account::getOrCreateForSystem('system.gateway.external.universe', $gateway);
            $accountExternal = Account::getOrCreateForSystem('system.gateway.external', $gateway);

            $newTransactionOrder = new self();
            $newTransactionOrder->status = 'waitForTreasurer';
            $newTransactionOrder->gatewayId = $gateway->id;
            $newTransactionOrder->accountFromId = $accountExternal->id;
            $newTransactionOrder->accountFromType = $accountExternal->type;
            $newTransactionOrder->accountToId = $accountUniverse->id;
            $newTransactionOrder->accountToType = $accountUniverse->type;
            $newTransactionOrder->createdAt = TIME;
            $newTransactionOrder->createdBy = $userId;
            $newTransactionOrder->currency = $gateway->currency;
            $newTransactionOrder->amount = $moneyToAccount;
            $newTransactionOrder->parentId = $transactionOrder->id;
            $newTransactionOrder->details = [
                'originalAccountId' => $transactionOrder->details['originalAccountId'],
                // копируются реквизиты платежной системы
                'details' => $transactionOrder->details['details']
            ];
            $newTransactionOrder->transactionGroupId = $transactionGroup;
            if (!$newTransactionOrder->save()) {
                throw new ModelException(_('Error with creating transaction order'), $newTransactionOrder->getErrors());
            }


            if ($gateway->type == 'bitcoin') {
                $response = Service::sendRequest(
                    $gateway->details['url'],
                    [
                        'action' => 'requestSend',
                        'transactionOrder' => $newTransactionOrder->id,
                        'address' => $transactionOrder->details['details']['address'],
                        'amount' => bcadd($moneyToAccount, '0', 8)
                    ],
                    $gateway->secureData['salt']
                );
                if (!$response || !$response['success']) {
                    throw new ModelException($response['message']);
                }
            }
        }

        return true;
    }

    public static function create(array $data, $userId)
    {
        $transactionOrder = new TransactionOrder();
        $transactionOrder->status = ArrayHelper::getFromArray($data, 'status');
        $transactionOrder->gatewayId = ArrayHelper::getFromArray($data, 'gatewayId');
        $transactionOrder->accountFromId = ArrayHelper::getFromArray($data, 'accountFromId');
        $transactionOrder->accountFromType = ArrayHelper::getFromArray($data, 'accountFromType');
        $transactionOrder->accountToId = ArrayHelper::getFromArray($data, 'accountToId');
        $transactionOrder->accountToType = ArrayHelper::getFromArray($data, 'accountToType');
        $transactionOrder->createdAt = TIME;
        $transactionOrder->createdBy = $userId;
        $transactionOrder->finishedAt = null;
        $transactionOrder->finishedBy = null;
        $transactionOrder->currency = ArrayHelper::getFromArray($data, 'currency');
        $transactionOrder->amount = ArrayHelper::getFromArray($data, 'amount');
        $transactionOrder->details = ArrayHelper::getFromArray($data, 'details', []);
        $transactionOrder->gatewaySearchHash = ArrayHelper::getFromArray($data, 'gatewaySearchHash');
        $transactionOrder->comment = ArrayHelper::getFromArray($data, 'comment');

        $transactionOrder->save();

        return $transactionOrder;
    }

    private static function getListCriteria(array $filters)
    {
        $query = ArrayHelper::getFromArray($filters, 'query');
        $accountId = ArrayHelper::getFromArray($filters, 'accountId');
        $accountToId = ArrayHelper::getFromArray($filters, 'accountToId');
        $accountFromId = ArrayHelper::getFromArray($filters, 'accountFromId');
        $gatewayId = ArrayHelper::getFromArray($filters, 'gatewayId');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');
        $amountFrom = ArrayHelper::getFromArray($filters, 'amountFrom');
        $amountTo = ArrayHelper::getFromArray($filters, 'amountTo');
        $status = ArrayHelper::getFromArray($filters, 'status');
        $ticketIdNull = ArrayHelper::getFromArray($filters, 'ticketIdNull');
        $firstAccountantIdNot = ArrayHelper::getFromArray($filters, 'firstAccountantIdNot');

        $criteria = new CDbCriteria();

        if (!empty($gatewayId)) {
            $criteria->compare('gatewayId', $gatewayId);
        }
        if (!empty($status)) {
            if (!is_array($status)) {
                $status = [$status];
            }
            $criteria->addInCondition('status', $status);
        }
        if (!empty($query)) {
            $criteria->addSearchCondition('comment', $query);
        }
        if (!empty($accountId)) {
            $criteria->addCondition('accountFromId=:accountId OR accountToId=:accountId');
            $criteria->params[':accountId'] = $accountId;
        }
        if (!empty($accountToId)) {
            $accountToId = is_array($accountToId) ? $accountToId : [$accountToId];
            $criteria->addInCondition('accountToId', $accountToId);
        }
        if (!empty($accountFromId)) {
            $accountFromId = is_array($accountFromId) ? $accountFromId : [$accountFromId];
            $criteria->addInCondition('accountFromId', $accountFromId);
        }
        if ($ticketIdNull) {
            $criteria->addCondition('ticketId IS NULL');
        }
        if ($firstAccountantIdNot) {
            $criteria->addCondition("(`firstAccountantId` IS NULL OR `firstAccountantId` <> :firstAccountantIdNot)");
            $criteria->params[':firstAccountantIdNot'] = $firstAccountantIdNot;
        }

        if ($dateFrom) {
            \ListCriteria::dateCriteria($criteria, $dateFrom, $dateTo);
        }
        if ($amountFrom) {
            \ListCriteria::dateCriteria($criteria, $amountFrom, $amountTo);
        }

        return $criteria;
    }


    public static function getList(array $filters, array &$pagination)
    {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = self::getListCriteria($filters);
        if ($limit) {
            $pagination['total'] = (int)self::model()->count($criteria);
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }

    public static function identifyByTicket(TransactionOrder $transactionOrder, Ticket $ticket, $userId)
    {
        if ($transactionOrder->ticketId) { //уже есть тикет
            throw new ModelException(_('Ticket already assigned'));
        }

        if ($transactionOrder->status !== 'completed') { //не завершена
            throw new ModelException(_('Transaction Order not completed'));
        }

        $accountFrom = Account::get($transactionOrder->accountFromId);
        if ($accountFrom->type !== 'system.gateway.external.universe.unknown') { //не неопознанная
            throw new ModelException(_('Payment must be unknown'));
        }

        $gateway = Gateway::get($transactionOrder->gatewayId);
        if (!$gateway) {
            throw new ModelException(_('Gateway not found'));
        }

        $dbTransaction = $transactionOrder->dbConnection->beginTransaction();
        try {
            $accountGatewayInternal = Account::getOrCreateForSystem('system.gateway.internal', $gateway);
            $accountUser = Account::getOrCreateForUser($ticket->createdBy, 'user.trading', $transactionOrder->currency);


            $newTransactionOrder = new TransactionOrder();
            $newTransactionOrder->accountFromId = $accountGatewayInternal->id;
            $newTransactionOrder->accountFromType = $accountGatewayInternal->type;
            $newTransactionOrder->accountToId = $accountUser->id;
            $newTransactionOrder->accountToType = $accountUser->type;
            $newTransactionOrder->currency = $transactionOrder->currency;
            $newTransactionOrder->amount = $transactionOrder->amount;
            $newTransactionOrder->status = 'waitForAccountant';
            $newTransactionOrder->gatewayId = $transactionOrder->gatewayId;
            $newTransactionOrder->createdAt = TIME;
            $newTransactionOrder->createdBy = $userId;
            $newTransactionOrder->parentId = $transactionOrder->id;
            $newTransactionOrder->details = [
                'accountantConfirmed' => [],
            ];
            $newTransactionOrder->gatewaySearchHash = null;
            $newTransactionOrder->ticketId = $ticket->id;
            $newTransactionOrder->comment = sprintf(
                "найденый платеж на сумму %s %s по тикету #%s",
                $transactionOrder->amount,
                $transactionOrder->currency,
                $ticket->id
            );
            if (!$newTransactionOrder->save()) {
                throw new ModelException(_('Transaction Order not saved'), $newTransactionOrder->getErrors());
            }


            $transactionOrder->ticketId = $ticket->id;
            $transactionOrder->update(['ticketId']);

            $dbTransaction->commit();
            return true;
        }
        catch (Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }

    }

    public static function modify(TransactionOrder $transactionOrder, array $data, $userId)
    {
        $status = ArrayHelper::getFromArray($data, 'status');
        $validation = [];
        if (!empty($status) && $transactionOrder->status !== $status) {
            $validation[] = 'status';
            $transactionOrder->status = $status;
        }
        if (!$validation) {
            return false;
        }
        if ($transactionOrder->save(true, $validation)) {
            return true;
        } else {
            throw new ModelException($transactionOrder->getErrors());
        }
    }

    public function updateStatus($status, array $details = array())
    {

    }


    public static function getAggregateStatByGateway(array $gateways, array $filters = [])
    {
        $gatewayIds = [];
        foreach ($gateways as $gateway) {
            $gatewayIds[$gateway->id] = [
                'amountSum' => 0,
                'count' => 0,
            ];
        }
        if ($gatewayIds) {
            $criteria = self::getListCriteria($filters);
            $criteria->select = 'gatewayId, sum(amount) as amountSum, count(id) as count';
            $criteria->addInCondition('gatewayId', array_keys($gatewayIds));
            $criteria->group = 'gatewayId';
            $data = self::model()->dbConnection->commandBuilder->createFindCommand('transaction_order', $criteria)->queryAll();
            foreach ($data as $row) {
                $gatewayIds[$row['gatewayId']] = ['amountSum' => $row['amountSum'], 'count' => $row['count']];
            }
        }
        return $gatewayIds;
    }
}