<?php

class VerificationController extends AdminController {
    
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);
        
        return true;
    }
    
    public function actionViewUserForModeration() {
        
        try {
            $users = User::getForModeration($this->paginationOptions);
            $data = array(
                'count' => (isset($this->paginationOptions['total']))?$this->paginationOptions['total']:'',
                'data' => $users
            );
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($data);
    }
    
    public function actionGetUserDoc() {
        $userId = $this->getParam('userId');
        
        try {
            $files = File::getUserDoc($userId);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($files);
    }
    
    public function actionVerifyUser() {
        $userId = $this->getParam('userId');
        
        try {
            $status = User::verify($userId);
            $logMessage = 'Verifying user with "'.$userId.'"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountVerified');
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($status);
    }
    
    
    
    public function actionRefuseUser() {
        $userId = $this->getParam('id');
        $reason = $this->getParam('reason');
        
        try {
            $status = User::refuse($userId, $reason);
            $logMessage = 'Refuse user with "'.$userId.'". Reason: '.$reason;
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountRejected');
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($status);
    }
    
    
}
