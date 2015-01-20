<?php

class AdminController extends MainController {
    
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
    
    private static $accessControl = array(
        'support' => array(
            'ticket' => array('ViewActiveTickets', 'ViewTicket', 'ReplyForTickets'),
        ),
        'ssupport' => array(
            'ticket' => array('ViewActiveTickets', 'ViewTicket', 'ReplyForTickets'),
        ),
        'accountant' => array(
            'stat' => array('ByFiatAddress'),
        ),
        'saccountant' => array(
            'stat' => array('ByFiatAddress'),
        ),
        'treasurer' => array(
            'transaction' => array('ExternalTransactions', 'AproveTransaction', 'RejectTransaction'),
        ),
        'streasurer' => array(
            'transaction' => array('ExternalTransactions', 'AproveTransaction', 'RejectTransaction'),
        ),
        'verifier' => array(
            'verification' => array('ViewUserForMoredation', 'GetUserDoc', 'VerifyUser', 'RefuseUser'),
        ),
        'sverifier' => array(
            'verification' => array('ViewUserForMoredation', 'GetUserDoc', 'VerifyUser', 'RefuseUser'),
        ),
        'admin' => array(
            'news' => array('AddNews', 'ModifyNews','GetPdf', 'GetAllNews'),
            'user' => array('SendInviteByEmail', 'LockUser', 'UnlockUser', 'RemoveUser'),
        )
    );
    
    public static function getRules($role) {
        if(isset(self::$accessControl[$role])) {
            return self::$accessControl[$role];
        }
        return null;
    }
    
    private function checkAccess($role, $controller, $method) {
        if($role == 'super') {
            return true;
        }
        
        if(isset(self::$accessControl[$role])) {
            if(isset(self::$accessControl[$role][$controller])) {
                if(in_array($method, self::$accessControl[$role][$controller])) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
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