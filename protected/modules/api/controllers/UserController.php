<?php

class UserController extends MainController {

    private $guestControl = array('login', 'register', 'activate', 'continueregister', 'lostpassword', 'changepasswordrequest', 'repairbyalarm', 'iwantearly');
    private $fullControl = array('islogged');
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(!(Yii::app()->user->isGuest ^ in_array(mb_strtolower($action->id), $this->guestControl)) || in_array(mb_strtolower($action->id), $this->fullControl)) {
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
    
    public function actionIsLogged() {
        $response = array('logged' => !Yii::app()->user->isGuest);
        if ($response['logged']) {
            $user = User::getCurrent();
            if($user) {
                $data = User::getLoginData(User::getCurrent());
                $response = array_merge($response, $data);
            } else {
                $response['logged'] = false;
                Yii::app()->session->destroy();
            }
        } else {
            $currency = Yii::app()->params->currency;
            
            $response['logged'] = false;
            $response['defaultPair'] = 0;
            $response['baseCurrency'] = 1;
            $response['currency'] = $currency;
            $response['supportedCurrency'] = Account::getSupportedCurrency()['derived'];
        }
        Response::ResponseSuccess($response);
    }
    
    public function actionActivate($id) {
        $frontUrl = Yii::app()->params['frontUrl'];
        
        try {
            $user = User::verifyNewUser($id);
        } catch(ExceptionUser $e) {
            $resultMessage = '';
            if($e instanceof ExceptionUserVerification) {
                $resultMessage = $e->getMessage();
            } else {
                $resultMessage = 'Unknow error';
            }
            
            header("Location: ".$frontUrl.'/registration/fail');
            exit();
        }

        $auth = new UserIdentity($user->email, '');
        
        $auth->setAutologin();
        $auth->setId($user->id);
        Yii::app()->user->allowAutoLogin = true;
        Yii::app()->user->login($auth, 2592000);
        $user->lastLoginAt = TIME;
        $user->save(true, array('lastLoginAt'));
        Loger::logUser(Yii::app()->user->id, 'User has logged in', 'login');
        
        header("Location: ".$frontUrl.'/registration/success');
    }
    
    public function actionRegister() {
        
        $email = $this->getParam('email', null);
        $password = $this->getParam('password', null);
        $inviteCode = $this->getParam('invite', false);
        $agree = $this->getParam('agree', false);
        $code = $this->getParam('code', false);
        
        $user = new User();
        try {
            if(is_null($email)) {
                throw new Exception('Wrong email parameter');
            }
            
            if($agree === '0' || $agree === 'false') {
                throw new Exception('You have to accept Terms of Agreement');
            }
            
            if(is_null($password)) {
                throw new Exception('Wrong password parameter');
            }
            
            if(Yii::app()->params->registerByInvite || ($inviteCode!=false)) {
                $status = $user->registerByInvite($inviteCode, $code);
            } else {
                $status = $user->registerUser($email, $password, $code);
            }
        } catch(Exception $e) {
            Response::ResponseError('Error: '.$e->getMessage());
        }
    
        if($status) {
            Response::ResponseSuccess(array(), 'User has registered. Conformation email sent to your address.');
        } else {
            Response::ResponseError('Unknow error');
        }
    }
    
    public function actionChangePasswordRequest() {
        $password = $this->getParam('password', null);
        
        try {
            User::changePassword($password);
            User::lostPassword($password);
        } catch(ExceptionUserVerification $e) {
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess();
        return true;
    }
    
    public function actionChangePassword() {
        
        $password = $this->getParam('password', true);
        $newPassword = $this->getParam('newPassword', true);
        
        try {
            User::changePassword($password, $newPassword);
        } catch(ExceptionWrongInputData $e) {
            Response::ResponseError('Wrong data');
        }
        
        Response::ResponseSuccess(array(),'Password successfuly changed');
    }
    
    public function actionContinueRegister() {
        //TODO: Make post
        $cid = $this->getParam('cid', false);
        $password = $this->getParam('password', true);
        $passwordConfirm = $this->getParam('passwordConfirm', false);
        $phoneNumber = $this->getParam('phone', false);
        
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
        $email = $this->getParam('email', false);
        
        try {
            User::lostPassword($email);
        } catch(ExceptionUserVerification $e) {
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess();
    }
    
    public function actionLogin() {

        $email = $this->getParam('email', false);
        $password = $this->getParam('password', false);
        
        $keepLogin = $this->getParam('keepLogin', false);
        $auth = new UserIdentity($email, $password);
        if($auth->authenticate()) {
            if($keepLogin === '1' || $keepLogin === 'true') {
                Yii::app()->user->allowAutoLogin = true;
                Yii::app()->user->login($auth, 2592000);
            } else {
                Yii::app()->user->login($auth);
            }
            $user = User::getCurrent();
            $user->lastLoginAt = TIME;
            $user->save(true, array('lastLoginAt'));
            $data = User::getLoginData($user);
            
            Loger::logUser(Yii::app()->user->id, 'User has logged in', 'login');
            Response::ResponseSuccess($data, 'User has logged');
        } else {
            Response::ResponseError($auth->errorMessage);
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
        $alarmCode = $this->getParam('code');
        $password = $this->getParam('password');
        $passwordConfirm = $this->getParam('passwordConfirm', false);
        
        if($password == $passwordConfirm) {
            if(AlarmCode::accessByCode($alarmCode, $password)) {
                Response::ResponseSuccess();
            }
        }
        Response::ResponseError();
    }
    
    public function actionIWantEarly() {
        $email = $this->getParam('email');
        try {
            UserEarly::add($email);
        } catch(Exception $e) {
            Response::ResponseError('', $e->getCode());
        }
        
        Response::ResponseSuccess('Subscribed !');
    }
    
}