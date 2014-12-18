<?php

class AlarmCode extends CActiveRecord
{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'alarm_code';
    }

    public function rules() {
        return [
        ];
    }
    
    public static function saveCodes($userId, $codes) {
        if(!is_numeric($userId)) {
            return false;
        }
        
        $sql = 'INSERT INTO alarm_code ("userId", "code") VALUES ';
        $sqlFrames = array();
        
        foreach($codes as $value) {
            $sqlFrames[] = '('.$userId.', \''.$value.'\')';
        }
        
        $sql .= implode(',', $sqlFrames);
        return Yii::app()->db->createCommand($sql)->execute();
    }
    
    
    public static function accessByCode($alarmCode, $password) {
        $code = self::model()->findByAttributes(array('code'=>$alarmCode));
        if(!$code) {
            return false;
        }
        
        $user = User::model()->findByPk($code->userId);
        if(!$user) {
            return false;
        }
        
        $user->password = UserIdentity::trickyPasswordEncoding($user->email, $password);
        if(!$user->save(true, array('password'))) {
            return false;
        }
        
        $code->delete();
        Loger::logUser($user->id, 'User has used an alarm code');
        
        return MailSender::sendEmail('alarmCode', $user->email, array('alarmCode'=>$alarmCode));
    }
    
    
}
