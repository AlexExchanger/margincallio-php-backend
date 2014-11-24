<?php

class Order extends CActiveRecord
{
    public static $tickerOptions = ['USDBTC', 'EURBTC'];
    public static $typeOptions = ['MARKET', 'LIMIT'];
    public static $sideOptions = ['BUY', 'SELL'];
    public static $statusOptions = ['pendingAccepted', 'accepted', 'filled', 'partialFilled', 'pendingCancelled', 'cancelled'];

    public static $pair = 'BTCUSD';
    
    public static function model($className = __CLASS__) 
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'order_'.self::$pair;
    }

    public function rules()
    {
        return array(
            /*array('ticker, type, side', 'filter', 'filter' => 'strtoupper'),
            array('ticker', 'in', 'allowEmpty' => false, 'range' => self::$tickerOptions, 'strict' => true),
            array('type', 'in', 'allowEmpty' => false, 'range' => self::$typeOptions, 'strict' => true),
            array('side', 'in', 'allowEmpty' => false, 'range' => self::$sideOptions, 'strict' => true),
            array('status', 'in', 'allowEmpty' => false, 'range' => self::$statusOptions, 'strict' => true),
            array('price', 'numerical', 'allowEmpty' => false, 'min' => 0.00001, 'max' => 100000, 'integerOnly' => false),
            array('price', 'validatorAccuracy', 'maxAccuracy' => 5),
            array('size', 'numerical', 'allowEmpty' => false, 'min' => 0.0001, 'max' => 100000, 'integerOnly' => false),
            array('size', 'validatorAccuracy', 'maxAccuracy' => 4), */
        );
    }

    public static function create(array $data, $userId)
    {
        $order = new Order();
        
        //Count
        $order->size = ArrayHelper::getFromArray($data, 'amount');
        //Price
        $order->price = ArrayHelper::getFromArray($data, 'rate');
        //Buy, sell
        $order->side = ArrayHelper::getFromArray($data, 'side');
        //Market, limit
        $order->type = ArrayHelper::getFromArray($data, 'type');
        $order->status = ArrayHelper::getFromArray($data, 'status') ? : 'pendingAccepted';
        $order->userId = $userId;
        $order->createdAt = TIME;
        $order->updatedAt = null;
        
        $guid = ArrayHelper::getFromArray($data, 'guid');
        if ($guid && Guid::validate($guid)) {
            $order->guid = $guid;
        } else {
            $order->guid = Guid::generate();
        }

        if (!$order->validate()) {
            throw new ModelException($order->getErrors());
        }

        if (!$order->save(false)) {
            throw new ModelException('Order was not created');
        }
        
        return true;
        
        /*$ticker = Ticker::getByTicker($order->ticker);
        if (!$ticker) {
            throw new ModelException('Unknown ticker');
        }*/

        /*$dbTransaction = $order->dbConnection->beginTransaction();

        try {
            $accountTickerCommission = Account::getOrCreateForSystem("system.ticker.$ticker->ticker.commission", $currencies[0]);

            // получим аккаунты в режиме лока (внутри транзакции)
            $accountFrom = Account::getForUpdate($accountFrom->id);
            $accountLock = Account::get($accountLock->id);

            $order->accounts = [
                'from' => [
                    'id' => $accountFrom->id,
                    'guid' => $accountFrom->guid,
                    'currency' => $accountFrom->currency
                ],
                'to' => [
                    'id' => $accountTo->id,
                    'guid' => $accountTo->guid,
                    'currency' => $accountTo->currency
                ],
                'lock' => [
                    'id' => $accountLock->id,
                    'guid' => $accountLock->guid,
                    'currency' => $accountLock->currency
                ],
                'ref' => !$accountRef ? null : [
                        'id' => $accountRef->id,
                        'currency' => $accountRef->currency,
                        // userId здесь, чтобы при исполнении ордера получить группу пользователя
                        'userId' => $user->refId,
                        // транзитный аккаунт. на него будут зачислены деньги и тут же списаны
                        'transitId' => $accountRefTransit->id
                    ],
                'tickerCommission' => [
                    'id' => $accountTickerCommission->id,
                    'currency' => $accountTickerCommission->currency
                ]
            ];

            $transferMoney = $order->side == 'BUY' ? bcmul($order->size, $order->price) : $order->size;
            if (bccomp(bcadd($accountFrom->balance, $accountFrom->creditLimit), $transferMoney) == -1) {
                throw new ModelException(
                    _('Not enough funds'),
                    [
                        'accountId' => $accountFrom->publicId,
                        'currency' => $accountFrom->currency,
                        'balance' => $accountFrom->balance,
                    ]
                );
            }

            $order->rest = $transferMoney;

            if (!$order->save(false)) {
                throw new ModelException(_('Order was not created'));
            }

            $accountFrom->saveCounters(['balance' => "-$transferMoney"]);
            $accountLock->saveCounters(['balance' => $transferMoney]);

            $transactionGroup = Guid::generate();
            $transaction1 = new Transaction();
            $transaction1->accountId = $accountFrom->id;
            $transaction1->debit = 0;
            $transaction1->credit = $transferMoney;
            $transaction1->createdAt = TIME;
            $transaction1->groupId = $transactionGroup;
            $transaction1->orderId = $order->id;
            if (!$transaction1->save(true)) {
                throw new ModelException(_('Transaction was not created'));
            }

            $transaction2 = new Transaction();
            $transaction2->accountId = $accountLock->id;
            $transaction2->debit = $transferMoney;
            $transaction2->credit = 0;
            $transaction2->createdAt = TIME;
            $transaction2->groupId = $transactionGroup;
            $transaction2->orderId = $order->id;
            if (!$transaction2->save(true)) {
                throw new ModelException(_('Transaction was not created'));
            }

            if (!$isMarketMaker) {
                Yii::app()->engine->createOrder(
                    $order->guid,
                    $order->ticker,
                    $order->side,
                    $order->type,
                    $order->price,
                    $order->size,
                    $order->side == 'BUY' ? $order->rest : bcmul($order->price, $order->rest),
                    $accountFrom->guid,
                    $accountTo->guid,
                    $order->operationId,
                    $brokerId
                );
            }

            $dbTransaction->commit();

            \Notify::updateAccountBalances([$accountFrom->id]);
        }
        catch (Exception $e) {
            $dbTransaction->rollback();
            throw $e;
        }*/

        return $order;
    }


    public static function cancel($orderId)
    {
        try {
            $order = self::getForUpdate($orderId);
            if (!$order) {
                throw new ModelException(_('Order not found'));
            }
            if (in_array($order->status, ['pendingCancelled', 'cancelled'])) {
                throw new ModelException(_('alreadyCancelled'));
            }
            if ($order->status == 'filled') {
                throw new ModelException(_('alreadyFilled'));
            }

            $result = Yii::app()->engine->cancelOrder(
                $order->guid,
                $order->ticker,
                $order->side,
                $order->size
            );

            if (!$result || !$result['success']) {
                throw new ModelException(_('Something wrong with engine'));
            }

            $history = $order->history;
            $history[] = ['createdAt' => TIME, 'action' => 'status', 'status' => 'pendingCancelled'];
            $order->history = $history;
            $order->status = 'pendingCancelled';
            $order->update(['status', 'history']);

            \UserLog::addAction($order->userId, 'orderCancelRequest', [
                'id' => $order->guid
            ]);

        }
        catch (Exception $e) {
            throw $e;
        }

        return true;
    }


    public static function get($id)
    {
        $order = null;
        if (is_numeric($id)) {
            $order = Order::model()->findByPk($id);
        } elseif (Guid::validate($id)) {
            $order = Order::model()->findByAttributes(['guid' => $id]);
        } else {
            throw new ModelException(_('id is incorrect'));
        }

        return $order;
    }


    public static function getForUpdate($id)
    {
        $order = null;
        if (is_numeric($id)) {
            $order = self::model()->dbConnection->createCommand('select * from `order` where id = :id limit 1 for update')
                ->queryRow(true, [':id' => $id]);
        } elseif (Guid::validate($id)) {
            $order = self::model()->dbConnection->createCommand('select * from `order` where guid = :guid limit 1 for update')
                ->queryRow(true, [':guid' => $id]);
        }

        if ($order) {
            $order = self::model()->populateRecord($order, true);
        }

        return $order;
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

    private static function getListCriteria(array $filters)
    {
        $status = ArrayHelper::getFromArray($filters, 'status');
        $ticker = ArrayHelper::getFromArray($filters, 'ticker');
        $side = ArrayHelper::getFromArray($filters, 'side');
        $type = ArrayHelper::getFromArray($filters, 'type');
        $userId = ArrayHelper::getFromArray($filters, 'userId');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');

        $criteria = new CDbCriteria();
        if (!empty($status)) {
            if (!is_array($status)) {
                $status = [$status];
            }
            $criteria->addInCondition('status', $status);
        }
        if (!empty($ticker)) {
            $criteria->compare('ticker', $ticker);
        }
        if (!empty($side)) {
            $criteria->compare('side', $side);
        }
        if (!empty($type)) {
            $criteria->compare('type', $type);
        }
        if (!empty($userId)) {
            $criteria->compare('userId', $userId);
        }
        ListCriteria::dateCriteria($criteria, $dateFrom, $dateTo, 'createdAt');

        return $criteria;
    }

    public static function getStat($group, array $filters = [])
    {
        $aggregate = [];

        switch ($group) {
            case 'ticker' :
                $options = Order::$tickerOptions;
                break;
            case 'type' :
                $options = Order::$typeOptions;
                break;
            case 'side' :
                $options = Order::$sideOptions;
                break;
            case 'status' :
                $options = Order::$statusOptions;
                break;
            default:
                throw new ModelException(_('Invalid group field'));
        }

        foreach ($options as $option) {
            $aggregate[$option] = 0;
        }

        $criteria = self::getListCriteria($filters);
        $criteria->select = "COUNT(*) as c, $group";
        $criteria->group = $group;
        $stats = self::model()->dbConnection->commandBuilder->createFindCommand('order', $criteria)->queryAll();

        foreach ($stats as $stat) {
            $aggregate[$stat[$group]] = (int)$stat['c'];
        }

        return $aggregate;
    }


    public static function getOpenOrders()
    {
        $criteria = new CDbCriteria();
        $criteria->addInCondition('status', ['pendingAccepted', 'pendingCancelled', 'accepted', 'partialFilled']);
        $orders = Order::model()->findAll($criteria);
        return $orders;
    }
    
    
    public static function createOrder($userId, $data) {

        $amount = ArrayHelper::getFromArray($data, 'amount');
        $side = ArrayHelper::getFromArray($data, 'side');
        $type = ArrayHelper::getFromArray($data, 'type');
        $rate = ArrayHelper::getFromArray($data, 'rate');
        
        //check amount
        if(!$amount) {
            throw new ExceptionWrongInputData();
        }
        
        //check available funds
        
        try {
            $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            if($type == 'LIMIT') {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_LIMIT_ORDER, $userId, $side, $amount, $rate));
            } elseif ($type == 'MARKET') {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_MARKET_ORDER, $userId, ($currency == 'USD')?1:0, $amount));
            } elseif ($type == 'INSTANT') {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_INSTANT_ORDER, $userId, ($currency == 'USD')?1:0, $amount));
            } else {
                throw new ExceptionUnknowOrderType();
            }
            
            $data['result'] = 'accepted'; 
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            throw $e;
        }
        
        //data[amount]
        $result = Order::create($data, $userId);
        return $result;
    }
    
    
}