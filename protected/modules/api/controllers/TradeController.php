<?php

class TradeController extends MainController { 

    private $user = null;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(!Yii::app()->user->isGuest) {
            $this->user = Yii::app()->user;
            return true;
        }
        
        Response::GetResponseError('Access denied');
        return false;
    }
    

    public function actionMakeOrder() {
        
        $orderSide = $this->getParam('side');
        $amount = $this->getParam('amount');
        $rate = $this->getParam('rate');
        $orderType = $this->getParam('type', false);
        
        //todo: for USD/BTC only
        
        $data = array(
            'amount' => $amount,
            'side' => ($orderSide === 0 || $orderSide  === 'true')? 1:0,
            'rate' => $rate,
            'type' => mb_strtoupper($orderType),
        );
        
        try {
            $result = Order::createOrder($this->user->id, $data);
            if(!$result) {
                throw new Exception('Order save error');
            }
            $logMessage = 'Created '.$data['side'].' order. Type: '.$data['type'].'. Amount: '.$amount.'.';
            $logMessage .= ($rate)? 'Rate: '.$rate.'.':'';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'makeOrder');
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError($e->getMessage());
        }
        Response::ResponseSuccess();
    }
    
    public function actionCancelOrder() {
        
        $orderId = $this->getParam('orderId');
        
        try {
            Order::cancelOrder($this->user->id, $orderId);
            $logMessage = 'Order with id '.$this->user->id.' canceled.';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'cancelOrder');
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionAddConditionalOrder() {
        
        //TODO: обсудить с фронтенд, как будет считаться направление условного ордера
        $availableTypes = array('STOPLOSS', 'TAKEPROFIT', 'TRAILINGSTOP');
        
        $type = mb_strtoupper($this->getParam('type'));
        
        if(!in_array($type, $availableTypes)) {
            Response::ResponseError('Wrong type for conditional order');
        }
        
        $orderSide = $this->getParam('side');
        
        $data = array(
            'type' => $type,
            'side' => ($orderSide === 0 || $orderSide  === 'true')? 1:0,
            'amount' => $this->getParam('amount'),
            'rate' => $this->getParam('rate'),
            'offset' => $this->getParam('offset')
         );
        
        try {
            $result = Order::createConditionalOrder($this->user->id, $data);
            
            $logMessage = 'Created '.$data['side'].' conditional order. Type: '.$data['type'].'. Amount: '.$data['amount'].'.';
            $logMessage .= ($data['rate'])? 'Rate: '.$data['rate'].'.':'';
            $logMessage .= ($data['offset'])? 'Offset: '.$data['offset'].'.':'';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'makeConditional');
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionCancelConditionalOrder() {
        
        //TODO: обсудить с фронтенд, как будет считаться направление условного ордера
        $availableTypes = array('STOPLOSS', 'TAKEPROFIT', 'TRAILINGSTOP');
        
        $type = mb_strtoupper($this->getParam('type'));
        $orderId = $this->getParam('orderId');
        
        if(!in_array($type, $availableTypes)) {
            Response::ResponseError('Wrong type for conditional order');
        }
        
        try {
            Order::cancelConditionalOrder($this->user->id, $orderId, $type);
            
            $logMessage = 'Conditional order with id '.$this->user->id.' canceled.';
            Loger::logUser(Yii::app()->user->id, $logMessage, 'cancelConditional');
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
}