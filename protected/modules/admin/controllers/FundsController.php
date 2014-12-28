<?php

class FundsController extends AdminController {
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }
    
    
}