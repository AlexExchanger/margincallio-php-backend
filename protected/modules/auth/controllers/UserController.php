<?php

class UserController extends CController {

    private $guestControl = array('login', 'register', 'activate');

    public function beforeAction($action) {
        if(!(Yii::app()->user->isGuest ^ in_array($action->id, $this->guestControl))) {
            return true;
        }
        
        print Response::ResponseError('Access denied');
        return false;
    }
    
    public function actionActivate($id) {
        try {
            User::verifyNewUser($id);
        } catch(ExceptionUser $e) {
            $resultMessage = '';
            if($e instanceof ExceptionUserVerification) {
                $resultMessage = $e->getMessage();
            } else {
                $resultMessage = 'Unknow error';
            }
            print Response::ResponseError($resultMessage);
            exit();
        }
        
        print Response::ResponseSuccess(array(), 'Successfuly verified');
    }
    

    public function actionRegister() {
        
        $email = Yii::app()->request->getParam('email');
        $inviteCode = Yii::app()->request->getParam('invite');
        
        $user = new User();
        try {
            $status = (Yii::app()->params->registerByInvite)? $user->registerByInvite($inviteCode):$user->registerUser($email);
        } catch(Exception $e) {
            print Response::ResponseError('Error: '.$e->getMessage());
            return;
        }
    
        if($status) {
            print Response::ResponseSuccess(array(), 'User has registered');
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