<?php

class ExternalGateway extends CActiveRecord{
    
    public function tableName() {
        return 'gateway';
    }
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    public function getBillingMeta() {}
    public function transferFrom() {}
    public function transferTo() {}
}