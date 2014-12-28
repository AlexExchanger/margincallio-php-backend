<?php

class Transaction extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'transaction';
    }

    public function rules() {
        return [
            ['accountId', 'numerical', 'allowEmpty' => false, 'min' => 1, 'integerOnly' => true],
            ['debit', 'numerical', 'allowEmpty' => false, 'min' => 0, 'max' => 1000000, 'integerOnly' => false],
            ['credit', 'numerical', 'allowEmpty' => false, 'min' => 0, 'max' => 1000000, 'integerOnly' => false],
            ['groupId', 'checkGuid'],
        ];
    }


    public function checkGuid($attribute, $params) {
        if (!Guid::validate($this->$attribute)) {
            $this->addError($this->$attribute, 'Guid invalid');
        }
    }

    public static function get($id) {
        return Transaction::model()->findByPk($id);
    }

    public static function create(array $data) {
        $model = new Transaction();
        $model->accountId = ArrayHelper::getFromArray($data, 'accountId');
        $model->debit = ArrayHelper::getFromArray($data, 'debit');
        $model->credit = ArrayHelper::getFromArray($data, 'credit');
        $model->groupId = ArrayHelper::getFromArray($data, 'groupId');
        $model->createdAt = TIME;

        try {
            if ($model->save()) {
                return $model;
            } else {
                throw new ModelException($model->getErrors());
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function getList(array $filters, array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = self::getListCriteria($filters);
        if ($limit) {
            $pagination['total'] = (int) self::model()->count($criteria);
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }

    public static function getStats(array $filters) {
        $result = [
            'all' => 0,
            'spend' => 0,
            'earn' => 0,
        ];
        $filters['direction'] = 'spend';
        $criteria = self::getListCriteria($filters);
        $result['spend'] = (int) self::model()->count($criteria);

        $filters['direction'] = 'earn';
        $criteria = self::getListCriteria($filters);
        $result['earn'] = (int) self::model()->count($criteria);

        $result['all'] = $result['spend'] + $result['earn'];
        return $result;
    }

    private static function getListCriteria(array $filters) {
        $accountId = ArrayHelper::getFromArray($filters, 'accountId');
        $direction = ArrayHelper::getFromArray($filters, 'direction');
        $currency = ArrayHelper::getFromArray($filters, 'currency');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');

        $criteria = new CDbCriteria();
        if (!empty($accountId)) {
            $criteria->compare('accountId', $accountId);
        }
        
        if (!empty($currency) && in_array($currency, Yii::app()->params['supportedCurrency'])) {
            $criteria->compare('currency', $currency);
        }

        if (!empty($direction)) {
            switch ($direction) {
                case 'spend':
                    $criteria->addCondition('credit > 0');
                    break;
                case 'earn':
                    $criteria->addCondition('debit > 0');
                    break;
            }
        }

        ListCriteria::timestampCriteria($criteria, $dateFrom, $dateTo);

        return $criteria;
    }

}
