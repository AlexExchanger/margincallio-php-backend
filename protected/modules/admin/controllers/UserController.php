<?php

class UserController extends AdminController {
    
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
    
    public function actionSendInviteByEmail() {
        $email = Yii::app()->request->getParam('email');
        
        try {
            UserInvite::SendInviteByEmail($email);
            $logMessage = 'Send an email to "'.$email.'"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
        } catch(Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess(array(), 'Invite successfuly sended');
    }
    
    public function actionLockUser() {
        $userId = Yii::app()->request->getParam('userId');
        
        try {
            User::LockUser($userId);
            $logMessage = 'Lock user with id "'.$userId.'"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountLocked');
        } catch(Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess(array(), 'User locked');
    }
    
    public function actionUnlockUser() {
        $userId = Yii::app()->request->getParam('userId');
        
        try {
            User::UnlockUser($userId);
            
            $logMessage = 'Unlock user with id "'.$userId.'"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountUnlocked');
        } catch(Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess(array(), 'User unlocked');
    }
    
    public function actionRemoveUser() {
        $userId = Yii::app()->request->getParam('userId');
        
        try {
            User::RemoveUser($userId);
            
            $logMessage = 'Remove user with id "'.$userId.'"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountRemoved');
        } catch(Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess(array(), 'User removed');
    }
    
    public function actionAll() {
        try {
            $result = User::getList($this->paginationOptions);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess(array(
            'count' => isset($this->paginationOptions['total']) ? $this->paginationOptions['total'] : '',
            'data' => $result
        ));
    }
    
    public function actionCreateUser() {
        $data = array(
            'email' => $this->getParam('email'),
            'password' => $this->getParam('password'),
            'type'  => $this->getParam('role'),
        );
        
        try {
            User::create($data);
        } catch(Exception $e) {
            Response::ResponseSuccess();
        }
        Response::ResponseSuccess();
    }
    
}