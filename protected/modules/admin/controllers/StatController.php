<?php

class StatController extends AdminController {

    public $paginationOptions;

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            Response::ResponseAccessDenied();
            return false;
        }

        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);

        return true;
    }
    
    public function actionGatewayStat() {
        $currency = $this->getParam('currency', false);
        $gatewayId = $this->getParam('gatewayId', false);
        
        $data = [
            'common' => array(
                'dateFrom' => $this->getParam('dateFrom'),
                'dateTo' => $this->getParam('dateTo'),
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
                    'dateFrom' => $this->getParam('dateFrom'),
                    'dateTo' => $this->getParam('dateTo'),
                    'status' => $this->getParam('status'),
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
            'userId' => $this->getParam('userId'),
            'address' => $this->getParam('address'),    
        ];
        
        try {
            $stat = Stat::getStatByFiat($data, [
                'common' => [
                    'dateFrom' => $this->getParam('dateFrom'),
                    'dateTo' => $this->getParam('dateTo'),
                    'status' => $this->getParam('status'),
                ],
                'pagination' => $this->paginationOptions,
            ]);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($stat);
    }
    
    public function actionLogStat() {
        try {
            $filters = array(
                'action' => $this->getParam('action'),
                'userId' => $this->getParam('userId', NULL),
                'ip' => $this->getParam('ip', NULL)
            );

            $logs = UserLog::getList($filters, $this->paginationOptions);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess($logs);
    }
    
    public function actionOrdersByUser() {
        
        $data = array(
            'userId' => $this->getParam('id'),
            'pair' => $this->getParam('pair', 'USD/BTC'), //Temporary solution
            'dateFrom' => $this->getParam('dateFrom', null),
            'dateTo' => $this->getParam('dateTo', null),
        );
        
        try {
            $orders = Order::getList($data, $this->paginationOptions);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess(array(
            'count' => isset($this->paginationOptions['total'])? $this->paginationOptions['total']:0,
            'data' => $orders
        ));
    }
    
    public function actionDealsByUser() {
        $data = array(
            'userBuyId' => $this->getParam('userBuyId'),
            'userSellId' => $this->getParam('userSellId'),
            'side' => $this->getParam('side'),
            'pair' => $this->getParam('pair', 'USD/BTC'), //Temporary solution
            'dateFrom' => $this->getParam('dateFrom', null),
            'dateTo' => $this->getParam('dateTo', null),
        );
        
        try {
            $deals = Deal::getList($data, $this->paginationOptions);
        } catch(Exception $e) {
            Response::ResponseError();
        }
        
        Response::ResponseSuccess(array(
            'count' => isset($this->paginationOptions['total'])? $this->paginationOptions['total']:0,
            'data' => $deals
        ));
    }
    
}