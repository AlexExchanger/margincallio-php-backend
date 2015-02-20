<?php

class TransactionExternal extends CActiveRecord {

    private $transactionStatus = [
        'pending',
        'done',
        'rejected'
    ];
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'transaction_external';
    }

    public function rules() {
        return [
//            ['accountId', 'numerical', 'allowEmpty' => false, 'min' => 1, 'integerOnly' => true],
//            ['debit', 'numerical', 'allowEmpty' => false, 'min' => 0, 'max' => 1000000, 'integerOnly' => false],
//            ['credit', 'numerical', 'allowEmpty' => false, 'min' => 0, 'max' => 1000000, 'integerOnly' => false],
        ];
    }

    public static function get($id) {
        return Transaction::model()->findByPk($id);
    }

    public static function create(array $data) {
        $model = new Transaction();
        $model->accountId = ArrayHelper::getFromArray($data, 'accountId');
        $model->debit = ArrayHelper::getFromArray($data, 'debit');
        $model->credit = ArrayHelper::getFromArray($data, 'credit');
        $model->createdAt = TIME;
        $model->status = 'pending';
        
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
        $pagination['total'] = (int) self::model()->count($criteria);
        if ($limit) {
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }

    public static function getStats(array $filters) {
        $result = [
            'all' => 0,
            'out' => 0,
            'in' => 0,
        ];
        $filters['direction'] = 'out';
        $criteria = self::getListCriteria($filters);
        $result['out'] = (int) self::model()->count($criteria);

        $filters['direction'] = 'in';
        $criteria = self::getListCriteria($filters);
        $result['in'] = (int) self::model()->count($criteria);

        $result['all'] = $result['out'] + $result['in'];
        return $result;
    }

    private static function getListCriteria(array $filters) {
        $accountId = ArrayHelper::getFromArray($filters, 'accountId');
        $direction = ArrayHelper::getFromArray($filters, 'direction');
        $dateFrom = ArrayHelper::getFromArray($filters, 'dateFrom');
        $dateTo = ArrayHelper::getFromArray($filters, 'dateTo');
        $status = ArrayHelper::getFromArray($filters, 'status');
        $type = ArrayHelper::getFromArray($filters, 'type', null);
        $gatewayId = ArrayHelper::getFromArray($filters, 'gatewayId');
        $currency = ArrayHelper::getFromArray($filters, 'currency');
        $balanceCriteria = ArrayHelper::getFromArray($filters, 'balance_criteria');
        
        $criteria = new CDbCriteria();
        if(!empty($balanceCriteria) && $balanceCriteria != '') {
            if($balanceCriteria == 'debet') {
                $criteria->compare('type', 't');
            } else {
                $criteria->compare('type', 'f');
            }
        }

        if (!empty($accountId)) {
            $criteria->compare('accountId', $accountId);
        }

        if(!empty($gatewayId)) {
            $criteria->compare('gatewayId', $gatewayId);
        }

        if(!empty($currency)) {
            $criteria->compare('currency', $currency);
        }

        if(!empty($status)) {
            $criteria->compare('verifyStatus', $status);
        }

        if(!is_null($type)) {
            $criteria->compare('type', $type);
        }
        
        if (!empty($direction)) {
            switch ($direction) {
                case 'out':
                    $criteria->addCondition('credit > 0');
                    break;
                case 'in':
                    $criteria->addCondition('debit > 0');
                    break;
            }
        }

        ListCriteria::timestampCriteria($criteria, $dateFrom, $dateTo);
        return $criteria;
    }

    public static function aproveTransaction($id) {
        
        $transaction = self::model()->findByPk($id);
        
        if(!$transaction) {
            return false;
        }
        
        $transaction->verifierId = Yii::app()->user->id;
        $transaction->status = 'done';
        
        return $transaction->save(true, ['verifierId', 'status']);
    }
    
    public static function rejectTransaction($id) {
        
        $transaction = self::model()->findByPk($id);
        
        if(!$transaction) {
            return false;
        }
        
        $transaction->verifierId = Yii::app()->user->id;
        $transaction->status = 'rejected';
        
        return $transaction->save(true, ['verifierId', 'status']);
    }
    

}
