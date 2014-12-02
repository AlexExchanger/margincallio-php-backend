<?php

class UserController extends CController {
    
    public function actionSendInviteByEmail() {
        $email = Yii::app()->request->getParam('email');
        
        try {
            UserInvite::SendInviteByEmail($email);
        } catch(Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }
        
        print Response::ResponseSuccess(array(), 'Invite successfuly sended');
    }
    
    public function actionLockUser() {
        $userId = Yii::app()->request->getParam('userId');
        
        try {
            User::LockUser($userId);
        } catch(Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }
        
        print Response::ResponseSuccess(array(), 'User locked');
    }
    
    public function actionUnlockUser() {
        $userId = Yii::app()->request->getParam('userId');
        
        try {
            User::UnlockUser($userId);
        } catch(Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }
        
        print Response::ResponseSuccess(array(), 'User unlocked');
    }
    
    public function actionRemoveUser() {
        $userId = Yii::app()->request->getParam('userId');
        
        try {
            User::RemoveUser($userId);
        } catch(Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }
        
        print Response::ResponseSuccess(array(), 'User removed');
    }
    
    
}