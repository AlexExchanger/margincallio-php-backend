<?php

class Candles extends CActiveRecord {
    
    private $tableName = null;
    private $pair = null;
    private $range = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        if(is_null($this->tableName)) {
            return 'candles_BTCUSD_1m';
        }        
        return $this->tableName;
    }
    
    public function __construct($pair, $range) {
        $this->tableName = implode('_', array('candles', $pair, $range));
        $this->pair = $pair;
        $this->range = $range;
    }
    
    public function getLast($begin, $end) {
        $criteria = new CDbCriteria();
        $criteria->addBetweenCondition('"timestamp"', $begin, $end);
        $criteria->order = '"timestamp" DESC';
        
        return $this->findAll($criteria);
    }
    
    public function parseAndSave($candles) {
        $candle = new Candles($this->pair, $this->range);
        
        foreach($candles as $value) {
            
        }
        
    }
    
}