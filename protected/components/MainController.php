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
        } else {
            $post_request = json_decode(file_get_contents("php://input"), true);
            $this->resource = $post_request['params'];
        }

        return true;
    }
    
    protected function getParam($item, $default=null) {
        if(isset($this->getResource[$item])) {
            return $this->getResource[$item];
        }
        
        return $default;
    }
    
    protected function getPost($item, $default=null) {
        if(isset($this->resource[$item])) {
            return $this->resource[$item];
        }
        
        return $default;
    }
}