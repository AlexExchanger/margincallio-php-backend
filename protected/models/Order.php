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

        $dbTransaction = Yii::app()->db->beginTransaction();
        try {
            
            $criteria = new CDbCriteria();
            $criteria->select = 'MAX(id) as "id"';
            $lastId = Order::model()->find($criteria)->id;
            
            $order->id = $lastId+1;
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

            if (!$order->validate()) {
                throw new ModelException($order->getErrors());
            }

            if (!$order->save(false)) {
                throw new ModelException('Order was not created');
            }
            
            $dbTransaction->commit();
        } catch (Exception $e) {
            $dbTransaction->rollback();
            return false;
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
            
            if(!isset($resultCore[0]) || $resultCore[0] != 0) {
                throw new Exception("User doesn't verified", 10012);
            }
        } catch (Exception $e) {
            if ($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            throw $e;
        }
        
        return true;
    }

    public static function getActiveOrders($userId) {
        $connector = new TcpRemoteClient(Yii::app()->params->coreUsdBtc);
        $response = $connector->sendRequest(array(TcpRemoteClient::FUNC_GET_ACTIVE_ORDERS, $userId));
        
        $data = array();
        
        foreach($response[1] as $value) {
            $status = 'accepted';
            if($value[3] == 0) {
                $status = 'filled';
            } elseif($value[2] != $value[3]) {
                $status = 'partialFilled';
            }
            
            $data[] = array(
                'id' => $value[0],
                'amount' => $value[2],
                'rate' => $value[4],
                'side' => false,
                'status' => $status,
                'timestamp' => Response::tickToTimestamp($value[6])
            );
        }
        
        foreach($response[2] as $value) {
            $status = 'accepted';
            if($value[3] == 0) {
                $status = 'filled';
            } elseif($value[2] != $value[3]) {
                $status = 'partialFilled';
            }
            
            $data[] = array(
                'id' => $value[0],
                'amount' => $value[2],
                'rate' => $value[4],
                'side' => true,
                'status' => $status,
                'timestamp' => Response::tickToTimestamp($value[6])
            );
        }
        
        return $data;
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
        $order->offset = $rate;
        $order->price = !isset($offset)? $rate:$offset;
        $order->userId = $userId;
        $order->guid = Guid::generate();
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
        
        if(!$order) {
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

        $criteria->order = '"createdAt" DESC';
        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        $result = self::model()->findAll($criteria);
        
        $data = array();
        foreach ($result as $value) {
            $data[] = array(
                'id' => (int)$value->id,
                'amount' => Response::bcScaleOut($value->size),
                'rate' => Response::bcScaleOut($value->price),
                'offset' => !is_null($value->offset)? Response::bcScaleOut($value->offset): null,
                'side' => $value->side,
                'order_type' => $value->type,
                'status' => $value->status,
                'createdAt' => Response::tickToTimestamp($value->createdAt),
                'updatedAt' => !is_null($value->updatedAt)? Response::tickToTimestamp($value->updatedAt):null,
            );
        }
        
        return $data;
    }
    
    private static function getListCriteria(array $filters)
    {
        $accountId = ArrayHelper::getFromArray($filters, 'userId');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');
        $types = ArrayHelper::getFromArray($filters, 'types', array());
       
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
