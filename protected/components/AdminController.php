<?php

class AdminController extends CController {
    
    private $roles = array(
        'trader',
        'admin',
        'support',
        'ssupport',
        'accountant',
        'saccountant',
        'treasurer',
        'streasurer',
        'verifier',
        'sverifier',
        'super'
    );
    
    private $accessControl = array(
        'support' => array(
            'ticket' => array('ViewActiveTickets', 'ViewTicket', 'ReplyForTickets'),
        ),
        'admin' => array(
            'news' => array('GetPdf'),
        )
    );
    
    private function checkAccess($role, $controller, $method) {
        if($role == 'super') {
            return true;
        }
        
        if(isset($this->accessControl[$role])) {
            if(isset($this->accessControl[$role][$controller])) {
                if(in_array($method, $this->accessControl[$role][$controller])) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function beforeAction($action) {
        $controller = $action->getController()->id;
        $method = $action->id;
        
        $user = User::model()->findByPk(Yii::app()->user->id);
        if(!$user) {
            return false;
        }

        $roles = explode('.', $user->type);
        foreach($roles as $value) {
            if($this->checkAccess($value, $controller, $method)) {
                return true;
            }
        }
        
        return false;
    }
}