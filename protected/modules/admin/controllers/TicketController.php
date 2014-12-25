<?php

class TicketController extends AdminController {
    
    public $paginationOptions;
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }

        $this->paginationOptions['limit'] = Yii::app()->request->getParam('limit', false);
        $this->paginationOptions['offset'] = Yii::app()->request->getParam('offset', false);
        $this->paginationOptions['sort'] = Yii::app()->request->getParam('sort', false);
        
        return true;
    }

    public function actionViewActiveTickets() {        
        $userId = Yii::app()->request->getParam('userId', false);
        try {
            $filters = array(
                'status' => 'waitForSupport',
                'userId' => ($userId != false)? $userId: null
            );
            
            $tickets = Ticket::getList($filters, $this->paginationOptions);
        } catch (Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }

        print Response::ResponseSuccess($tickets);
    }
    
    public function actionViewTicket() {        
        $ticketId = Yii::app()->request->getParam('ticketId', false);
        
        try {
            $messages = Ticket::getMessageList($ticketId, $this->paginationOptions);
        } catch (Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }

        print Response::ResponseSuccess($messages);
    }
    
    private function view($type) {
        $userId = Yii::app()->request->getParam('userId', false);
        $status = Yii::app()->request->getParam('status', 'waitForSupport');
        
        try {
            $filters = array(
                'status' => $status,
                'userId' => ($userId != false) ? $userId : null,
                'department' => $type,
            );

            $tickets = Ticket::getList($filters, $this->paginationOptions);
        } catch (Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }
        
        print Response::ResponseSuccess($tickets);
    }
    
    public function acitonViewGeneral() {
        $this->view('general');
    }
    
    public function acitonViewFinance() {
        $this->view('finance');
    }
    
    public function acitonViewVerification() {
        $this->view('verification');
    }
    
    public function acitonViewSecurity() {
        $this->view('security');
    }
    
    public function acitonViewPartners() {
        $this->view('partners');
    }
    
    public function actionReplyForTicket() {
        
        $ticketId = Yii::app()->request->getParam('ticketId', false);
        $text = Yii::app()->request->getParam('text');
        
        if(!$ticketId) {
            print Response::ResponseError();
            exit();
        }
        
        try {
            $ticket = Ticket::get($ticketId);
            Ticket::modify($ticket, array(), $text, 0);
            
            $logMessage = 'Reply for ticket with id "'.$ticketId.'" with message: '.$text;
            Loger::logAdmin(Yii::app()->user->id, $logMessage);
        } catch (Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();   
    }
    
}
