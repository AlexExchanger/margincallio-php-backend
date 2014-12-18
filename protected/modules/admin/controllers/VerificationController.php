<?php

class VerificationController extends CController {
    
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }

        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);
        
        return true;
    }
    
    public function actionViewUserForMoredation() {
        
        try {
            $users = User::getForModeration($this->paginationOptions);
        } catch(Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess($users);
    }
    
    public function actionGetUserDoc() {
        $userId = Yii::app()->request->getParam('id');
        
        try {
            $files = File::getUserDoc($userId);
        } catch(Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess($files);
    }
    
    public function actionVerifyUser() {
        $userId = Yii::app()->request->getParam('id');
        
        try {
            $status = User::verify($userId);
            $logMessage = 'Verifying user with "'.$userId.'"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountVerified');
        } catch(Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess($status);
    }
    
    public function actionRefuseUser() {
        $userId = Yii::app()->request->getParam('id');
        $reason = Yii::app()->request->getParam('reason');
        
        try {
            $status = User::refuse($userId, $reason);
            $logMessage = 'Refuse user with "'.$userId.'". Reason: '.$reason;
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountRejected');
        } catch(Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess($status);
    }
    
    
}
