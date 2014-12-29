<?php

class UserController extends MainController {

    private $guestControl = array('login', 'register', 'activate', 'continueregister', 'lostpassword', 'changepassword', 'changepasswordrequest', 'repairbyalarm');
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(!(Yii::app()->user->isGuest ^ in_array(mb_strtolower($action->id), $this->guestControl))) {
            return true;
        }
        
        Response::ResponseError('Access denied');
        return false;
    }
    
    public function actions() {
        return array(
            'captcha'=>array(
                'class'=>'CCaptchaAction',
            ),
        );
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
            
            Response::ResponseError($resultMessage);
        }
        
        Response::ResponseSuccess(array(), 'Successfuly verified');
    }
    
    public function actionRegister() {
        
        $email = $this->getPost('email');
        $password = $this->getPost('password');
        $inviteCode = $this->getPost('invite', false);
        
        $user = new User();
        try {
            if(Yii::app()->params->registerByInvite || ($inviteCode!=false)) {
                $status = $user->registerByInvite($inviteCode);
            } else {
                $status = $user->registerUser($email, $password);
            }
        } catch(Exception $e) {
            Response::ResponseError('Error: '.$e->getMessage());
        }
    
        if($status) {
            Response::ResponseSuccess(array(), 'User has registered');
        } else {
            Response::ResponseError('Unknow error');
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
            Response::ResponseError('Passwords are not equal');
        }
        
        try {
            User::changeLostPassword($cid, $newPassword);
        } catch(ExceptionWrongInputData $e) {
            Response::ResponseError('Wrong data');
        }
        
        Response::ResponseSuccess(array(),'Password successfuly changed');
    }
    
    public function actionContinueRegister() {
        //TODO: Make post
        $cid = Yii::app()->request->getParam('cid', false);
        $password = Yii::app()->request->getParam('password', true);
        $passwordConfirm = Yii::app()->request->getParam('passwordConfirm', false);
        $phoneNumber = Yii::app()->request->getParam('phone', false);
        
        if($password != $passwordConfirm) {
            Response::ResponseError('Passwords are not equal');
        } else {
            try {
                $user = User::continueVerifying($cid, $password, $phoneNumber);
                $codes = UserIdentity::generateAlarmCodes($user->id, $user->email);
                AlarmCode::saveCodes($user->id, $codes);
                
            } catch(Exception $e) {
                Response::ResponseError($e->getMessage());
            }
        }
        Response::ResponseSuccess(array('codes'=>$codes), 'Register complete');
    }
    
    public function actionLostPassword() {
        $email = Yii::app()->request->getParam('email', false);
        $phone = Yii::app()->request->getParam('phone', false);
        
        try {
            User::lostPassword($email, $phone);
        } catch(ExceptionUserVerification $e) {
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionLogin() {

        $email = Yii::app()->request->getParam('email', false);
        $password = Yii::app()->request->getParam('password', false);

        $auth = new UserIdentity($email, $password);
        if($auth->authenticate()) {
            Yii::app()->user->login($auth);
            $user = User::getCurrent();
            $user->lastLoginAt = TIME;
            $user->save(true, array('lastLoginAt'));
            $data = User::getLoginData($user);
            
            Loger::logUser(Yii::app()->user->id, 'User has logged in', 'login');
            Response::ResponseSuccess($data, 'User has logged');
        } else {
            Response::ResponseError();
        }
    }

    public function actionLogout() {
        if(!Yii::app()->user->isGuest) {
            $stats = User::getGeneralStatistic();
            $id = Yii::app()->user->id;
            Yii::app()->user->logout();
            Loger::logUser($id, 'User has logged out');
            Response::ResponseSuccess($stats);
        } else {
            Response::ResponseError('User is guest');
        }
    }
    
    public function actionGetCaptcha() {
        $this->render('captcha');
    }
    
    public function actionRepairByAlarm() {
        $alarmCode = Yii::app()->request->getParam('code');
        $password = Yii::app()->request->getParam('password');
        $passwordConfirm = Yii::app()->request->getParam('passwordConfirm', false);
        
        if($password == $passwordConfirm) {
            if(AlarmCode::accessByCode($alarmCode, $password)) {
                Response::ResponseSuccess();
            }
        }
        Response::ResponseError();
    }
}