<?php

class AccountController extends CController {

    private $user = null;
    
    public function beforeAction($action) {
    
        if(!Yii::app()->user->isGuest) {
            $this->user = Yii::app()->user;
            return true;
        }
        
        print Response::ResponseError('Access denied');
        return false;
    }
    
    public function actionGetWalletList() {
        
        //Example: BTC,USD
        $pair = Yii::app()->request->getParam('pair', 'BTC,USD');
        
        $accountList = Account::model()->findAllByAttributes(array(
            'userId'=>$this->user->id,
            'type'=> array('user.safeWallet'),
            ));
        
        if(!$accountList) {
            print Response::ResponseError('There are no wallets');
            exit;
        }
        
        $remoteAccountInfo = Account::getAccountInfo();
        
        $data = array();
        foreach($accountList as $key=>$value) {
            $data[] = array(
                'type' => 'safe',
                'currency' => $value->currency,
                'balance' => $value->balance, 
            );
        }
        
        $data[] = array(
            'type' => 'trade',
            'currency' => explode(',', $pair)[0],
            'balance' => (string)bcadd($remoteAccountInfo['firstAvailable'], 0)
        );
        
        $data[] = array(
            'type' => 'trade',
            'currency' => explode(',', $pair)[1],
            'balance' => (string)bcadd($remoteAccountInfo['secondAvailable'],0)
        );
        
        print Response::ResponseSuccess($data);
    }
    
    //type 0 = s to t, type 1 = t to s 
    public function actionTransferFunds() {
        $type = Yii::app()->request->getParam('type');
        $currency = Yii::app()->request->getParam('currency', false);
        $amount = Yii::app()->request->getParam('amount', false);
        
        if(!isset($type)) {
            print Response::ResponseError('No type');
            exit();
        }
        
        if(!in_array($currency, Yii::app()->params->supportedCurrency)) {
            print Response::ResponseError('Currency doesn\'t support');
            exit();
        }
        
        try {
            if ($currency == 'BTC') {
                if (!preg_match('~^\d+(\.\d{1,8})?$~', $amount)) {
                    throw new Exception('Wrong amount');
                }
            } elseif (!preg_match('~^\d+(\.\d{1,2})?$~', $amount)) {
                throw new Exception('Wrong amount');
            }
            
            if($type) {
                Account::transferToSafe($currency, $amount);
            } else {
                Account::transferToTrade($currency, $amount);   
            }
        } catch(Exception $e) {
            print Response::ResponseError($e->getMessage());
            exit();
        }
        
        print Response::ResponseSuccess();
    }   
    
    public function actionGetActiveOrders() {
        
        try {
            $orders = Order::getActiveOrders($this->user->id);
        } catch (Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                print TcpErrorHandler::TcpHandle($e->errorType);
                exit();
            }
        }
        
        /**
            first array - buy, second array - sell 
          
            22, - order ID
            1,  - original amount
            1,  - actual amount
            340, - rate
            635524464736530000 - ticks 
         */
        
        print Response::ResponseSuccess($orders);
    }
    
    public function actionGetActiveconditional() {
        
    }
    
    
}