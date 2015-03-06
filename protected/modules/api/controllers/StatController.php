<?php

class StatController extends MainController {
    
    private $user = null;
    public $paginationOptions;
    
    private $fullControl = array('mainstat');
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(Yii::app()->user->isGuest && !in_array(mb_strtolower($action->id), $this->fullControl)) {
            Response::ResponseError('Access denied');
            return false;
        }

        $this->user = Yii::app()->user;
        
        $this->paginationOptions['limit'] = $this->getParam('limit', false);
        $this->paginationOptions['offset'] = $this->getParam('offset', false);
        $this->paginationOptions['sort'] = $this->getParam('sort', false);
        
        return true;
    }
    
    public function actionMainStat() {
        $currency = $this->getParam('currency', 'BTC');
        
        try {
            $data = Stat::mainStat($currency);
        } catch(Exception $e) {
            Response::ResponseError($e->getMessage());
        }
        
        Response::ResponseSuccess($data);
    }
    
}