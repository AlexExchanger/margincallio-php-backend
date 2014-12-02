<?php

class TicketController extends CController {
    
    public $paginationOptions;
    
    public function beforeAction($action) {
        parent::beforeAction($action);

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
        } catch (Exception $e) {
            print Response::ResponseError();
            exit();
        }
        
        print Response::ResponseSuccess();   
    }
    
}
