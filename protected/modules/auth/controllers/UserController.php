<?php

class UserController extends CController {

    private $guestControl = array('login', 'register');

    public function beforeAction($action) {
        if(!(Yii::app()->user->isGuest ^ in_array($action->id, $this->guestControl))) {
            return true;
        }
        
        print Response::ResponseError('Access denied');
        return false;
    }

    public function actionRegister() {
        
        $email = Yii::app()->request->getParam('email');
        $inviteCode = Yii::app()->request->getParam('invite');
        
        $user = new User();
        try {
            $confirmCode = (Yii::app()->params->registerByInvite)? $user->registerByInvite($inviteCode):$user->registerUser($email);
        } catch(Exception $e) {
            print Response::ResponseError('Error: '.$e->getMessage());
            return;
        }
        
        if($confirmCode) {
            print Response::ResponseSuccess(array('confirmcode'=>$confirmCode), 'User has registered');
        } else {
            print Response::ResponseError('Unknow error');
        }
        
    }

    public function actionLogin() {

        $email = Yii::app()->request->getParam('email', false);
        $password = Yii::app()->request->getParam('password', false);

        $auth = new UserIdentity($email, $password);
        if($auth->authenticate()) {
            Yii::app()->user->login($auth);
            print Response::ResponseSuccess(array(), 'User has logged');
        } else {
            print Response::ResponseError('Error: '.$auth->errorMessage);
        }
    }

    public function actionLogout() {
        if(!Yii::app()->user->isGuest) {
            Yii::app()->user->logout();
            print Response::ResponseSuccess();
        } else {
            print Response::ResponseError('User is guest');
        }
    }

}