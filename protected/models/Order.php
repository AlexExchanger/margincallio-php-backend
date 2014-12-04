<?php

class Order extends CActiveRecord {

    public static $tickerOptions = ['USDBTC', 'EURBTC'];
    public static $typeOptions = ['MARKET', 'LIMIT'];
    public static $sideOptions = ['BUY', 'SELL'];
    public static $statusOptions = ['pendingAccepted', 'accepted', 'filled', 'partialFilled', 'pendingCancelled', 'cancelled'];
    public static $pair = 'BTCUSD';

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'order_' . self::$pair;
    }

    public function rules() {
        return array(
                /* array('ticker, type, side', 'filter', 'filter' => 'strtoupper'),
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

    public static function create(array $data, $userId) {
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
    }

    public static function cancelOrder($userId, $orderId) {
        if (!$userId && !$orderId) {
            throw new ExceptionWrongInputData();
        }

        $order = Order::model()->findByAttributes(array('userId' => $userId, 'id' => $orderId));

        if (!$order) {
            throw new ExceptionWrongInputData();
        }

        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $connector->sendRequest(array(TcpRemoteClient::FUNC_CANCEL_ORDER, $userId, $orderId));
        return true;
    }

    public static function createOrder($userId, $data) {

        $amount = ArrayHelper::getFromArray($data, 'amount');
        $side = ArrayHelper::getFromArray($data, 'side');
        $type = ArrayHelper::getFromArray($data, 'type');
        $rate = ArrayHelper::getFromArray($data, 'rate');

        //check amount
        if (!$amount) {
            throw new ExceptionWrongInputData();
        }

        //check available funds
        try {
            $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
            if ($type == 'LIMIT') {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_LIMIT_ORDER, $userId, $side, $amount, $rate));
            } elseif ($type == 'MARKET') {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_MARKET_ORDER, $userId, $side, $amount));
            } elseif ($type == 'INSTANT') {
                $resultCore = $connector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_INSTANT_ORDER, $userId, $side, $amount));
            } else {
                throw new ExceptionUnknowOrderType();
            }

            $data['status'] = 'accepted';
        } catch (Exception $e) {
            if ($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            throw $e;
        }

        Order::create($data, $userId);
        return $resultCore;
    }

    public static function getActiveOrders($userId) {

        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        return $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACTIVE_ORDERS, $userId));
    }
    
    public static function createConditionalOrder($userId, $data) {

        $type = ArrayHelper::getFromArray($data, 'type');
        $side = ArrayHelper::getFromArray($data, 'side');
        $amount = ArrayHelper::getFromArray($data, 'amount');
        $rate = ArrayHelper::getFromArray($data, 'rate');
        $offset = ArrayHelper::getFromArray($data, 'offset');
        $conector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $result = false;
        switch($type) {
            case 'STOPLOSS':
                $result = $conector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_SL, $userId, $side, $amount, $rate));
                break;
            case 'TAKEPROFIT':
                 $result = $conector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_TP, $userId, $side, $amount, $rate));
                break;
            case 'TRAILINGSTOP':
                $result = $conector->sendRequest(array(TcpRemoteClient::FUNC_CREATE_TS, $userId, $side, $amount, $offset));
                break;
        }
        
        $order = new Order();
        $order->coreId = $result[0];
        $order->type = $type;
        $order->side = $side;
        $order->size = $amount;
        $order->price = isset($offset)?$offset:$rate;
        $order->userId = $userId;
        $order->guid = Guid::generate();
        $order->status = 'accepted';
        $order->createdAt = TIME;
        $order->updatedAt = null;

        return $order->save();
    }
    
    public static function cancelConditionalOrder($userId, $orderId, $type) {

        $conector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        
        //find by database
        $order = Order::model()->findByAttributes(array(
            'userId'=>$userId,
            'coreId'=>$orderId,
            'type'=>$type,
            ));
        
        if(!$order || $order->status != 'accepted') {
            throw new ExceptionOrderNonExist();
        }
        
        
        $result = false;
        switch($type) {
            case 'STOPLOSS':
                $result = $conector->sendRequest(array(TcpRemoteClient::FUNC_CANCEL_SL, $userId, $orderId));
                break;
            case 'TAKEPROFIT':
                 $result = $conector->sendRequest(array(TcpRemoteClient::FUNC_CANCEL_TP, $userId, $orderId));
                break;
            case 'TRAILINGSTOP':
                $result = $conector->sendRequest(array(TcpRemoteClient::FUNC_CANCEL_TS, $userId, $orderId));
                break;
        }
        
        $order->status = 'cancelled';
        $order->updatedAt = TIME;
        
        return $order->save();
    }

    public static function getActiveConditionalOrders($userId) {
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        return $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACTIVE_CONDITIONAL_ORDER, $userId));
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
        $accountId = ArrayHelper::getFromArray($filters, 'userId');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');
        $types = ArrayHelper::getFromArray($filters, 'types');
       
        $criteria = new CDbCriteria();
        $conditions = array();
        $typeParams = array();
        foreach($types as $key=>$value) {
            $conditions[] = '("status"=:cond'.$key.')';
            $typeParams[':cond'.$key] = $value;
        }

        $criteria->condition .= implode(' OR ', $conditions);
        $criteria->params = array_merge($criteria->params, $typeParams);
       
       
        if (!empty($accountId)) {
            $criteria->compare('userId', $accountId);
        }

        ListCriteria::timestampCriteria($criteria, $dateFrom, $dateTo);
        
        return $criteria;
    }
    
}
