<?php

class UserController extends CController {

    private $guestControl = array('login', 'register', 'activate', 'continueregister', 'lostpassword', 'changepassword', 'changepasswordrequest');

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
            if(Yii::app()->params->registerByInvite || isset($inviteCode)) {
                $status = $user->registerByInvite($inviteCode);
            } else {
                $status = $user->registerUser($email);
            }
            
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
    
    
    
    
    public function actionChangePasswordRequest($id) {
        //TODO: action for front-end
        
        return true;
    }
    
    public function actionChangePassword() {
        $cid = Yii::app()->request->getParam('cid');
        $newPassword = Yii::app()->request->getParam('newPassword', true);
        $newPasswordConfurm = Yii::app()->request->getParam('newPasswordConfurm', false);
        
        if($newPassword != $newPasswordConfurm) {
            print Response::ResponseError('Passwords are not equal');
            exit();
        }
        
        try {
            User::changeLostPassword($cid, $newPassword);
        } catch(ExceptionWrongInputData $e) {
            print Response::ResponseError('Wrong data');
        }
        
        print Response::ResponseSuccess(array(),'Password successfuly changed');
    }
    
    public function actionContinueRegister() {
        //TODO: Make post
        $cid = Yii::app()->request->getParam('cid', false);
        $password = Yii::app()->request->getParam('password', true);
        $passwordConfirm = Yii::app()->request->getParam('passwordConfirm', false);
        $phoneNumber = Yii::app()->request->getParam('phone', false);
        
        if($password != $passwordConfirm) {
            print Response::ResponseError('Passwords are not equal');
            exit();
        } else {
            try {
                User::continueVerifying($cid, $password, $phoneNumber);
            } catch(Exception $e) {
                print Response::ResponseError($e->getMessage());
                exit();
            }
        }
        print Response::ResponseSuccess(array(), 'Register complete');
    }
    
    public function actionLostPassword() {
        $email = Yii::app()->request->getParam('email', false);
        $phone = Yii::app()->request->getParam('phone', false);
        
        try {
            User::lostPassword($email, $phone);
        } catch(ExceptionUserVerification $e) {
            print Response::ResponseError($e->getMessage());
        }
        
        print Response::ResponseSuccess();
        
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