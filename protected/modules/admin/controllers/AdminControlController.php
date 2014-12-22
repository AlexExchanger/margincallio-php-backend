<?php

class AdminControlController extends AdminController {
    
    public function actionGrantRole() {
        $userId = Yii::app()->request->getParam('userId');
        $role = Yii::app()->request->getParam('role');
        
        if($role == 'super') {
            print Response::ResponseError();
            exit();
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
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();
    }
    
    public function actionRevokeRole() {
        $userId = Yii::app()->request->getParam('userId');
        $role = Yii::app()->request->getParam('role');
        
        if($role == 'super') {
            print Response::ResponseError();
            exit();
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
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();
    }
}