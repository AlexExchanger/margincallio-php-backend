<?php

class TradeController extends CController { 

    private $user = null;
    
    public function beforeAction($action) {
    
        if(!Yii::app()->user->isGuest) {
            $this->user = Yii::app()->user;
            return true;
        }
        
        print Response::ResponseError('Access denied');
        return false;
    }
    

    public function actionMakeOrder() {
        
        $orderSide = Yii::app()->request->getParam('side');
        $amount = Yii::app()->request->getParam('amount');
        $rate = Yii::app()->request->getParam('rate');
        $orderType = Yii::app()->request->getParam('type', false);
        
        //todo: for USD/BTC only
        
        $data = array(
            'amount' => $amount,
            'side' => ($orderSide == 'buy')? 0:1,
            'rate' => $rate,
            'type' => mb_strtoupper($orderType),
        );
        
        try {
            $result = Order::createOrder($this->user->id, $data);
        } catch (Exception $e) {
            print Response::ResponseError($e->getMessage());
            exit();
        }

        /*
            Result:
                11, - id trade
                18, - buyer id
                18, - seller id
                0, - initiator kind (0 - buy order, 1 - sell order)
                1, - amount
                340, - rate
                0.002, - buyer fee
                0.68, - seller fee
                635524470082470000 - ticks datetime
         */
        
        print Response::ResponseSuccess($result);
    }
    
    public function actionCancelOrder() {
        
        $orderId = Yii::app()->request->getParam('orderId');
        
        try {
            Order::cancelOrder($this->user->id, $orderId);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();
    }
    
    public function actionAddConditionalOrder() {
        
        //TODO: обсудить с фронтенд, как будет считаться направление условного ордера
        $availableTypes = array('STOPLOSS', 'TAKEPROFIT', 'TRAILINGSTOP');
        
        $type = mb_strtoupper(Yii::app()->request->getParam('type'));
        
        if(!in_array($type, $availableTypes)) {
            print Response::ResponseError('Wrong type for conditional order');
            exit();
        }
        
        $data = array(
            'type' => $type,
            'side' => Yii::app()->request->getParam('side'),
            'amount' => Yii::app()->request->getParam('amount'),
            'rate' => Yii::app()->request->getParam('rate'),
            'offset' => Yii::app()->request->getParam('offset')
         );
        
        try {
            $result = Order::createConditionalOrder($this->user->id, $data);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();
    }
    
    public function actionCancelConditionalOrder() {
        
        //TODO: обсудить с фронтенд, как будет считаться направление условного ордера
        $availableTypes = array('STOPLOSS', 'TAKEPROFIT', 'TRAILINGSTOP');
        
        $type = mb_strtoupper(Yii::app()->request->getParam('type'));
        $orderId = Yii::app()->request->getParam('orderId');
        
        if(!in_array($type, $availableTypes)) {
            print Response::ResponseError('Wrong type for conditional order');
            exit();
        }
        
        try {
            Order::cancelConditionalOrder($this->user->id, $orderId, $type);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();
    }
    
}