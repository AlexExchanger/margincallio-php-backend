<?php

class TcpErrorHandler extends CComponent {
    
    public static function TcpHandle($code) {
        
        $message = '';
        
        switch($code) {
            case TcpRemoteClient::ErrorAccountAlreadyExists:
                $message = 'Account already exist';
                break;
            case TcpErrorHandler::ErrorOrderNotFound:
                $message = 'Order Not Found';
                break;
            case TcpRemoteClient::ErrorInsufficientMarketVolume:
                $message = 'There is no money at this wallet or the order book is empty';
                break;
            case TcpRemoteClient::ErrorIncorrectRate:
                $message = 'Wrong rate paramether';
                break;
            case TcpRemoteClient::ErrorUnknown:
                $message = 'Unknow error';
                break;
        } 

        return Response::ResponseError($message);
    }
    
}