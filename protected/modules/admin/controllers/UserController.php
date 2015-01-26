<?php

class UserController extends AdminController {
    
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
    
    public function actionSendInviteByEmail() {
        $email = $this->getParam('email');
        
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
        $userId = $this->getParam('userId');
        $email = $this->getParam('email');
        
        try {
            User::LockUser($userId, $email);
            $logMessage = 'Lock user with id "'.$userId.'"';
            Loger::logAdmin(Yii::app()->user->id, $logMessage, 'accountLocked');
        } catch(Exception $e) {
            Response::ResponseError('Unknow error');
        }
        
        Response::ResponseSuccess(array(), 'User locked');
    }
    
    public function actionUnlockUser() {
        $userId = $this->getParam('userId');
        
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
        $userId = $this->getParam('userId');
        
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
            User::getUsers($result);
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
    
    public function actionResetUserPassword() {
        $id = $this->getParam('id');
        
        try {
            User::resetPassword($id);
        } catch (Exception $e){
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionSetUserPassword() {
        $id = $this->getParam('id');
        $password = $this->getParam('password', false);
        
        try {
            User::setPassword($id, $password);
        } catch (Exception $e){
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionResetTwoFA() {
        $id = $this->getParam('id');
        
        try {
            User::resetTwoFA($id);
        } catch (Exception $e){
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionChangeEmail() {
        $id = $this->getParam('id');
        $email = $this->getParam('email');
        
        try {
            User::changeEmail($id, $email);
        } catch (Exception $e){
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionGetById() {
        
        $id = $this->getParam('id');
        
        try {
            $user = User::getList($this->paginationOptions, $id);
            if(!$user) {
                throw new Exception();
            }
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($user);
    }
}