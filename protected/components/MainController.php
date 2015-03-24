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
    
    protected function preflight() {
        header('Content-Type: application/json');
        
        $allowDomains = array(
            'http://stock.bit',
            'http://spacebtc.tk',
            'http://dev.stock.bit',
            'http://dev.stock.loc',
            'http://dev.stock.loc',
            'http://admin.stock.bit',
            'http://dev.admin.stock.bit',
            'http://admin.stock.loc',
            'http://dev.admin.stock.loc',
            'http://landing.spacebtc.tk',
            'http://landing.stock.loc',
            'http://admin.spacebtc.tk');
        
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
        header('Access-Control-Allow-Credentials: true');
        
        if(isset($_SERVER['HTTP_ORIGIN'])) {
            if(in_array($_SERVER['HTTP_ORIGIN'], $allowDomains)) {
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
            }
        }
    }
}