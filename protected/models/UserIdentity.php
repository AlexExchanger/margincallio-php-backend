<?php

class UserIdentity extends CUserIdentity {

    private $_id;

    public static function trickyPasswordEncoding($username, $password) {
        $usernameLength = mb_strlen($username);
        $finalUserName = '';
        if($usernameLength%2 != 0) {
            for($i=0;$i!=round($usernameLength/2)-1;$i++) {
                $finalUserName .= mb_substr($username, $usernameLength-($i+1), 1);
                $finalUserName .= mb_substr($username, $i, 1);
            }
            $finalUserName .= mb_substr($username, round($usernameLength/2)-1, 1);
        } else {
            for($i=0;$i!=$usernameLength/2;$i++) {
                $finalUserName .= mb_substr($username, $i, 1);
                $finalUserName .= mb_substr($username, $usernameLength-($i+1), 1);
            }
            $finalUserName = strrev($finalUserName);
        }

        $finalPass = hash('sha512', $finalUserName.$password);
        return $finalPass;
    }
    
    
    public static function generateAlarmCodes($userId, $email) {
        
        $generator = hash('sha512', (($userId*3)/2).$email);
        $code = array();
        for($i=0; $i<10; $i++) {
            if($i%2 != 0) {
                $code[] = hash('sha512', md5($i).$generator);
            } else {
                $code[] = hash('sha512', $generator.md5($i));
            }
        }
        
        return $code;
    }
    

    public function authenticate() {
        $record = User::model()->findByAttributes(array('email'=>  mb_strtolower($this->username)));
        if($record === null) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
            $this->errorMessage = 'Invalid username or password';
        } else if($record->password !== self::trickyPasswordEncoding($this->username, $this->password)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
            $this->errorMessage = 'Invalid username or password';
        } else if(!is_null($record->emailVerification)) {
            $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
            $this->errorMessage = 'User not activated';
        } else {
            $this->_id = $record->id;
            $this->errorCode = self::ERROR_NONE;
        }
        return !$this->errorCode;
    }

    public function getId() {
        return $this->_id;
    }

}