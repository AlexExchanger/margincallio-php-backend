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
        $accountList = Account::model()->findAllByAttributes(array(
            'userId'=>$this->user->id,
            'type'=> array('user.trading', 'user.safeWallet'),
            ));
        
        if(!$accountList) {
            print Response::ResponseError('There are no wallets');
            exit;
        }
        
        $data = array();
        foreach($accountList as $key=>$value) {
            $data[] = array(
                'type' => ($value->type == 'user.trading')? 'trading':'safe',
                'currency' => $value->currency,
                'balance' => $value->balance, 
            );
        }
        
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
}