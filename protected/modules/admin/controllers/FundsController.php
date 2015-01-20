<?php

class FundsController extends AdminController {
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        return true;
    }
    
    public function actionConvert() {
        $pair = $this->getParam('pair');
        $value = $this->getParam('value');
        
        try {
            $response = array(
                'dir' => Funds::convertFunds($value, $pair, true),
                'rev' => Funds::convertFunds($value, $pair, false)
            );
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($response);
    }
    
    public function actionAddPair() {
        $pair = $this->getParam('pair');
        $value = $this->getParam('value');
        
        try {
            if(!Funds::addPairRate($pair, $value)) {
                throw new Exception();
            }
            
            $logMessage = 'Add new pair "'.$pair.'" with rate: '.$value;
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        
        Response::ResponseSuccess();
    }
    
    public function actionUpdatePair() {
        $id = $this->getParam('id');
        $value = $this->getParam('value');
        
        try {
            if(!Funds::updatePairRate($id, $value)) {
                throw new Exception();
            }
            $logMessage = 'Update a pair with an id "'.$id.'". New rate: '.$value;
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
}