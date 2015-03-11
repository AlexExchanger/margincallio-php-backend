<?php

class Deal extends CActiveRecord {

    public static $pair = 'BTCUSD';

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'deal';
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
        $criteria->order = '"createdAt" DESC';
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
        
        $orderBuyId = ArrayHelper::getFromArray($filters, 'orderBuyId', null);
        $orderSellId = ArrayHelper::getFromArray($filters, 'orderSellId', null);
        
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');
        $side = ArrayHelper::getFromArray($filters, 'side', null);
        
        $currency = ArrayHelper::getFromArray($filters, 'currency', null);
       
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

        if(!is_null($orderBuyId)) {
            $criteria->compare('orderBuyId', $orderBuyId);
        }
        
        if(!is_null($orderSellId)) {
            $criteria->compare('orderSellId', $orderSellId);
        }
        
        if(!is_null($currency)) {
            $criteria->compare('currency', $currency);
        }

        ListCriteria::timestampCriteria($criteria, $dateFrom, $dateTo);
        
        return $criteria;
    }
    
    
    
}
