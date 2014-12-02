<?php

class UserPhone extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'user_phone';
    }

    public function rules() {
        return array(
            array('id, phone', 'required'),
            array('phone', 'application.components.validators.phoneValidator'),
            array('id, phone', 'safe'),
        );
    }
    
    public function addPhone() {
        if(!$this->save()) {
            throw new ExceptionUserPhone();
        }
        return true;
    }
    
    
}
