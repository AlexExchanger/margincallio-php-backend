<?php

class UserController extends CController {
    
    public function actionSendInviteByEmail() {
        $email = Yii::app()->request->getParam('email');
        
        try {
            UserInvite::SendInviteByEmail($email);
        } catch(Exception $e) {
            print Response::ResponseError('Unknow error');
            exit();
        }
        
        print Response::ResponseSuccess(array(), 'Invite successfuly sended');
    }
    
}