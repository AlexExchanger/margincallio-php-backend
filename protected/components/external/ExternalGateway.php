<?php

class ExternalGateway extends CActiveRecord{
    
    public function tableName() {
        return 'gateway';
    }
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    public static function processPayment($data, $payment) {
        $gateway = GatewayFactory::create($data['gatewayId']);
        if(!$gateway) {
            return false;
        }
        
        $paymentFormJSON = $gateway->payment;
        $paymentForm = json_decode($paymentFormJSON, true);
        $userForm = json_decode($payment, true);
       
        foreach($paymentForm as $group) {
            foreach($group['fields'] as $field) {
                if($field['required'] == true) {
                    if(!isset($userForm[$field['name']])) {
                        return false;
                    }
                } else {
                    if(!isset($userForm[$field['name']])) {
                        continue;
                    }
                }
                
                switch($field['type']) {
                    case 'String':
                        if(!is_string($userForm[$field['name']])) {
                            return false;
                        }
                        break;
                    case 'Checkbox':
                        if(!is_bool($userForm[$field['name']])) {
                            return false;
                        }
                        break;
                }
            }
        }
        
        $transaction = new TransactionExternal();
        $transaction->gatewayId = $data['gatewayId'];
        $transaction->type = false;
        $transaction->status = 'pending';
        $transaction->accountId = $data['accountId'];
        $transaction->amount = $data['amount'];
        $transaction->createdAt = TIME;
        $transaction->currency = $data['currency'];
        $transaction->details = json_encode($userForm);
        
        return $transaction->save();
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

    private static function getListCriteria(array $filters) {
        $type = ArrayHelper::getFromArray($filters, 'type', null);
        $currency = ArrayHelper::getFromArray($filters, 'currency', null);

        $criteria = new CDbCriteria();
        if (!is_null($type)) {
            $criteria->addSearchCondition('type', $type);
        }

        if (!is_null($currency)) {
            $criteria->compare('currency', $currency);
        }

        return $criteria;
    }
    
    public static function grantFunds($data) {
        if(!isset($data['currency']) || !isset($data['amount']) || !isset($data['userId'])) {
            return false;
        }
        
        $gatewayId = 0;
        switch($data['currency']) {
            case 'USD':
                $gatewayId = 4;
                break;
            case 'BTC':
                $gatewayId = 5;
                break;
            default:
                return false;
                break;
        }
        
        $gateway = GatewayFactory::create($gatewayId);
        
        $userAccount = Account::model()->findByAttributes(array(
            'userId' => $data['userId'],
            'currency' => $data['currency'],
            'type' => 'user.safeWallet'
        ));
        
        if(!$userAccount) {
            return false;
        }
        
        $transaction = new TransactionExternal();
        $transaction->gatewayId = $gatewayId;
        $transaction->type = false;
        $transaction->verifyStatus = 'pending';
        $transaction->accountId = $userAccount->id;
        $transaction->amount = $data['amount'];
        $transaction->createdAt = TIME;
        $transaction->currency = $data['currency'];

        if(!$transaction->save()) {
            throw new Exception();
        }
        
        return $gateway->transferTo($userAccount->id, $transaction->id, $data['amount']);
    }
    
    public function getBillingMeta() {}
    public function transferFrom($accountId, $transactionId, $amount) {}
    public function transferTo($accountId, $transactionId, $amount) {}
}