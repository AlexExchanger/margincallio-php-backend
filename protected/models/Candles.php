<?php

class Candles extends CActiveRecord {
    
    private $tableName = null;
    private $pair = null;
    private $range = null;
    private $currency = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        if(is_null($this->tableName)) {
            return 'candles_1m';
        }        
        return $this->tableName;
    }
    
    public function __construct($pair, $range, $currency) {
        $this->tableName = 'candles_'.$range;
        $this->pair = $pair;
        $this->range = $range;
        $this->currency = $currency;
    }
    
    public function getLast($begin, $end) {
        $criteria = new CDbCriteria();
        $criteria->addBetweenCondition('"timestamp"', $begin, $end);
        $criteria->compare('currency', $this->currency);
        $criteria->order = '"timestamp" DESC';
        
        return $this->findAll($criteria);
    }
    
    public function parseAndSave($candles) {
        $candle = new Candles($this->pair, $this->range);
        
        foreach($candles as $value) {
            
        }
        
    }
    
}