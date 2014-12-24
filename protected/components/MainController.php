<?php

class MainController extends CController {
    
    private $resource = [];
    private $getResource = [];
    
    public function beforeAction($action) {
        if(!parent::beforeAction($action)) {
            return false;
        }
        
        if(!empty($_GET)) {
            $this->getResource = $_GET;
        }
        
        if(!empty($_POST) && isset($_POST['request'])) {
            $this->resource = CJSON::decode($_POST['request'], true);
        }
        
        return true;
    }
    
    protected function getParam($item, $default=false) {
        if(isset($this->getResource[$item])) {
            return $this->getResource[$item];
        }
        
        return $default;
    }
    
    protected function getPost($item, $default=false) {
        if(isset($this->resource[$item])) {
            return $this->resource[$item];
        }
        
        return $default;
    }
}