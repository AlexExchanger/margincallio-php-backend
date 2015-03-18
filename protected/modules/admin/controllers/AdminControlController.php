<?php

class AdminControlController extends AdminController {
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }
        return true;
    }
    
    public function actionGrantRole() {
        $userId = $this->getParam('userId');
        $role = $this->getParam('role');
        
        if($role == 'super') {
            Response::ResponseError();
        }
        
        try {
            $user = User::model()->findByPk($userId);
            if(!$user) {
                throw new Exception();
            }
            
            $roles = explode('.', $user->type);
            if(!in_array($role, $roles) && in_array($role, User::$typeOptions)) {
                array_push($roles, $role);
            }
            
            $user->type = implode('.', $roles);
            if(!$user->save(true, array('type'))) {
                throw new Exception();
            }
            
            $logMessage = 'Grand role "'.$role.'" to '.$userId;
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
            
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionRevokeRole() {
        $userId = $this->getParam('userId');
        $role = $this->getParam('role');
        
        if($role == 'super') {
            Response::ResponseError();
        }
        
        try {
            $user = User::model()->findByPk($userId);
            if(!$user) {
                throw new Exception();
            }
            
            $roles = explode('.', $user->type);
            
            $roleKey = array_search($role, $roles);
            if(in_array($role, $roles)) {
                unset($roles[$roleKey]);
            }
            $user->type = implode('.', $roles);
            if(!$user->save(true, array('type'))) {
                throw new Exception();
            }
            
            $logMessage = 'Revoke role "'.$role.'" from '.$userId;
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
            
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess();
    }
    
    
    public function actionMakeSnapshot() {
        try {
            $connection = new TcpRemoteClient();
            $connection->sendRequest(array(TcpRemoteClient::FUNC_MAKE_SNAPSHOT));
        } catch(Exception $e) {
            if($e instanceof ExceptionTcpRemoteClient) {
                TcpErrorHandler::TcpHandle($e->errorType);
            }
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess('Success');
    }
    
    public function actionLockMarket() {
        
        try {
            $connection = new TcpRemoteClient();
            $connection->sendRequest(array(TcpRemoteClient::FUNC_CLOSE_MARKET));            
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess('Locked');
    }
    
    public function actionUnlockMarket() {
        
        try {
            $connection = new TcpRemoteClient();
            $connection->sendRequest(array(TcpRemoteClient::FUNC_OPEN_MARKET));            
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess('Unlocked');
    }
    
    
}