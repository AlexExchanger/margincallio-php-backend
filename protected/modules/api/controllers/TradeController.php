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
    

    public function actionMakeLimitOrder() {
        
        $orderSide = Yii::app()->request->getParam('side');
        $amount = Yii::app()->request->getParam('amount');
        $rate = Yii::app()->request->getParam('rate');
        
        //todo: for USD/BTC only

        $data = array(
            'amount' => $amount,
            'side' => ($orderSide == 'buy')? 0:1,
            'rate' => $rate,
            'type' => 'LIMIT',
        );
        
        try {
            Order::createOrder($this->user->id, $data);
        } catch (Exception $e) {
            print Response::ResponseError($e->getMessage());
            exit();
        }
        
        print Response::ResponseSuccess();
    }
    
    public function actionMakeMarketOrder() {
        
    }
    
    
}