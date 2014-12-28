<?php

class TradeController extends CController { 

    private $user = null;
    
    public function beforeAction($action) {
    
        if(!Yii::app()->user->isGuest) {
            $this->user = Yii::app()->user;
            return true;
        }
        
        Response::GetResponseError('Access denied');
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

            $logMessage = 'Created '.$data['side'].' order. Type: '.$data['type'].'. Amount: '.$amount.'.';
            $logMessage .= ($rate)? 'Rate: '.$rate.'.':'';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'makeOrder');
        } catch (Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        Response::ResponseSuccess($result);
    }
    
    public function actionCancelOrder() {
        
        $orderId = Yii::app()->request->getParam('orderId');
        
        try {
            Order::cancelOrder($this->user->id, $orderId);
            $logMessage = 'Order with id '.$this->user->id.' canceled.';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'cancelOrder');
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionAddConditionalOrder() {
        
        //TODO: обсудить с фронтенд, как будет считаться направление условного ордера
        $availableTypes = array('STOPLOSS', 'TAKEPROFIT', 'TRAILINGSTOP');
        
        $type = mb_strtoupper(Yii::app()->request->getParam('type'));
        
        if(!in_array($type, $availableTypes)) {
            Response::ResponseError('Wrong type for conditional order');
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
            
            $logMessage = 'Created '.$data['side'].' conditional order. Type: '.$data['type'].'. Amount: '.$data['amount'].'.';
            $logMessage .= ($data['rate'])? 'Rate: '.$data['rate'].'.':'';
            $logMessage .= ($data['offset'])? 'Offset: '.$data['offset'].'.':'';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'makeConditional');
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionCancelConditionalOrder() {
        
        //TODO: обсудить с фронтенд, как будет считаться направление условного ордера
        $availableTypes = array('STOPLOSS', 'TAKEPROFIT', 'TRAILINGSTOP');
        
        $type = mb_strtoupper(Yii::app()->request->getParam('type'));
        $orderId = Yii::app()->request->getParam('orderId');
        
        if(!in_array($type, $availableTypes)) {
            Response::ResponseError('Wrong type for conditional order');
        }
        
        try {
            Order::cancelConditionalOrder($this->user->id, $orderId, $type);
            
            $logMessage = 'Conditional order with id '.$this->user->id.' canceled.';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'cancelConditional');
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
}