<?php

class Deal extends CActiveRecord {

    public static $pair = 'BTCUSD';

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'deal_' . self::$pair;
    }

    public function rules() {
        return array();
    }
    
    public static function getList(array $filters, array &$pagination)
    {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = self::getListCriteria($filters);
        
        $pagination['total'] = (int)self::model()->count($criteria);
        if ($limit) {
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }
    
    private static function getListCriteria(array $filters)
    {
        $userBuyId = ArrayHelper::getFromArray($filters, 'userBuyId', null);
        $userSellId = ArrayHelper::getFromArray($filters, 'userSellId', null);
        
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');
        $side = ArrayHelper::getFromArray($filters, 'side', null);
       
        $criteria = new CDbCriteria();
        
        if(!is_null($side)) {
            $criteria->compare('side', $side);
        }
        
        if(!is_null($userBuyId)) {
            $criteria->compare('userBuyId', $userBuyId);
        }
        
        if(!is_null($userSellId)) {
            $criteria->compare('userSellId', $userSellId);
        }

        ListCriteria::timestampCriteria($criteria, $dateFrom, $dateTo);
        
        return $criteria;
    }
    
    
    
}
