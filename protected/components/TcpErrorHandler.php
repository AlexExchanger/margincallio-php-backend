<?php

class TcpErrorHandler extends CComponent {
    
    public static function TcpHandle($code) {
        
        $message = '';
        
        switch($code) {
            case TcpRemoteClient::ErrorAccountAlreadyExists:
                $message = 'Account already exist';
                break;
            case TcpRemoteClient::ErrorInsufficientMarketVolume:
                $message = 'There is no money at this wallet or the order book is empty';
                break;
            case TcpRemoteClient::ErrorUnknown:
                $message = 'Unknow error';
                break;
        } 

        return Response::ResponseError($message);
    }
    
}