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


    public function authenticate() {
        $record = User::model()->findByAttributes(array('email'=>$this->username));
        if($record === null) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        } else if($record->password !== self::trickyPasswordEncoding($this->username, $this->password)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
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