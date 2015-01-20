<?php

class StatController extends AdminController {

    public $paginationOptions;

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);

        return true;
    }
    
    public function actionGatewayStat() {
        $currency = Yii::app()->request->getParam('currency', false);
        $gatewayId = Yii::app()->request->getParam('gatewayId', false);
        
        $data = [
            'common' => array(
                'dateFrom' => Yii::app()->request->getParam('dateFrom'),
                'dateTo' => Yii::app()->request->getParam('dateTo'),
                'currency' => $currency,
                'gatewayId' => $gatewayId
            ),
            'pagination' => $this->paginationOptions,
        ];
        
        try {
            $stat = Stat::getStatByGateway($currency, $data);
        } catch (Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($stat);
    }
    
    public function actionByUser() {
        
        $data = array(
            'userId' => $this->getParam('userId'),
            'currency' => $this->getParam('currency'),
        );
        
        try {
            $stat = Stat::getFullStatByUser($data, [
                'common' => [
                    'dateFrom' => Yii::app()->request->getParam('dateFrom'),
                    'dateTo' => Yii::app()->request->getParam('dateTo'),
                    'status' => Yii::app()->request->getParam('status'),
                ],
                'pagination' => $this->paginationOptions,
            ]);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($stat);
    }
    
    
    public function actionByFiatAddress() {

        $data = [
            'userId' => Yii::app()->request->getParam('userId'),
            'address' => Yii::app()->request->getParam('address'),    
        ];
        
        try {
            $stat = Stat::getStatByFiat($data, [
                'common' => [
                    'dateFrom' => Yii::app()->request->getParam('dateFrom'),
                    'dateTo' => Yii::app()->request->getParam('dateTo'),
                    'status' => Yii::app()->request->getParam('status'),
                ],
                'pagination' => $this->paginationOptions,
            ]);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($stat);
    }

}
