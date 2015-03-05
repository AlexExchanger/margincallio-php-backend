<?php

class TcpErrorHandler extends CComponent {
    
    public static function TcpHandle($code) {
        
        $message = '';
        
        switch($code) {
            case TcpRemoteClient::ErrorAccountAlreadyExists:
                $message = 'Account already exist';
                break;
            case TcpRemoteClient::ErrorAccountNotFound:
                $message = 'Account not found';
                break;
            case TcpRemoteClient::ErrorAccountAlreadySuspended:
                $message = 'Account already suspended';
                break;
            case TcpRemoteClient::ErrorAccountAlreadyUnsuspended:
                $message = 'Account already unsuspended';
                break;
            case TcpRemoteClient::ErrorAccountSuspended:
                $message = 'Error with account suspending';
                break;
            case TcpRemoteClient::ErrorCrossUserAccessDenied:
                $message = 'Cross user access denied';
                break;
            case TcpRemoteClient::ErrorInsufficientFunds:
                $message = 'Insufficient funds';
                break;
            case TcpRemoteClient::ErrorIncorrectOrderKind:
                $message = 'Incorrect order kind';
                break;
            case TcpRemoteClient::ErrorOrderNotFound:
                $message = 'Order Not Found';
                break;
            case TcpRemoteClient::ErrorInsufficientMarketVolume:
                $message = 'There is no money at this wallet or the order book is empty';
                break;
            case TcpRemoteClient::ErrorBorrowedFundsUse:
                $message = 'Error with borrowed funds use';
                break;
            case TcpRemoteClient::ErrorNegativeOrZeroSum:
                $message = 'Sum is negative or zero';
                break;
            case TcpRemoteClient::ErrorNegativeOrZeroId:
                $message = 'Id is negative or zero';
                break;
            case TcpRemoteClient::ErrorApiKeyNotPrivileged:
                $message = 'This api key doesn\'t privileged';
                break;
            case TcpRemoteClient::ErrorIncorrectStopLossRate:
                $message = 'Incorrect stop loss rate';
                break;
            case TcpRemoteClient::ErrorIncorrectTakeProfitRate:
                $message = 'Incorrect take profit rate';
                break;
            case TcpRemoteClient::ErrorIncorrectTrailingStopOffset:
                $message = 'Incorrect trailing stop offset';
                break;
            case TcpRemoteClient::ErrorApiKeysLimitReached:
                $message = 'Api key\'s limit reached';
                break;
            case TcpRemoteClient::ErrorApiKeyNotFound:
                $message = 'Api key not found';
                break;
            case TcpRemoteClient::ErrorSignatureDuplicate:
                $message = 'Signature duplicate';
                break;
            case TcpRemoteClient::ErrorNonceLessThanExpected:
                $message = 'Nonce less than expected';
                break;
            case TcpRemoteClient::ErrorIncorrectSignature:
                $message = 'Incorrect signature';
                break;
            case TcpRemoteClient::ErrorNegativeOrZeroLimit:
                $message = 'Limit is negative or zero';
                break;
            case TcpRemoteClient::ErrorInvalidFunctionArguments:
                $message = 'Invalid argument';
                break;
            case TcpRemoteClient::ErrorFunctionNotFound:
                $message = 'Function not found';
                break;
            case TcpRemoteClient::ErrorInvalidJsonInput:
                $message = 'Invalid input json data';
                break;
            case TcpRemoteClient::ErrorNegativeOrZeroLeverage:
                $message = 'Leverage is negative or zero';
                break;
            case TcpRemoteClient::ErrorIncorrectPercValue:
                $message = 'Incorrect perc value';
                break;
            case TcpRemoteClient::ErrorFixAccountsLimitReached:
                $message = 'Fix account\'s limit reached';
                break;
            case TcpRemoteClient::ErrorFixRestartFailed:
                $message = 'Fix restart failed';
                break;
            case TcpRemoteClient::ErrorFixAccountAlreadyExists:
                $message = 'Fix account already exist';
                break;
            case TcpRemoteClient::ErrorFixAccountNotFound:
                $message = 'Fix account not found';
                break;
            case TcpRemoteClient::ErrorFixSymbolNotFound:
                $message = 'Fix symbol not found';
                break;
            case TcpRemoteClient::ErrorFixFieldsNotSet:
                $message = 'Fix field not set';
                break;
            case TcpRemoteClient::ErrorFixInvalidClOrdID:
                $message = 'Invalid fix Cl or Id';
                break;
            case TcpRemoteClient::ErrorFixUnknownOrderType:
                $message = 'Fix unknow order type';
                break;
            case TcpRemoteClient::ErrorFixInvalidOrderId:
                $message = 'Fix invalid order id';
                break;
            case TcpRemoteClient::ErrorSnapshotBackupFailed:
                $message = 'Snapshot backup failed';
                break;
            case TcpRemoteClient::ErrorSnapshotRestoreFailed:
                $message = 'Snapshot restore failed';
                break;
            case TcpRemoteClient::ErrorMarketClosed:
                $message = 'Marked closed';
                break;
            case TcpRemoteClient::ErrorMarketAlreadyClosed:
                $message = 'Marked already closed';
                break;
            case TcpRemoteClient::ErrorMarketAlreadyOpened:
                $message = 'Marked already opened';
                break;
            case TcpRemoteClient::ErrorMarketOpened:
                $message = 'Marked opened';
                break;
            case TcpRemoteClient::ErrorBackupRestoreInProc:
                $message = 'Backup restore in proccess';
                break;
            case TcpRemoteClient::ErrorIPDuplicate:
                $message = 'IP Duplicate';
                break;
            case TcpRemoteClient::ErrorInvalidCurrency:
                $message = 'Invalid currency';
                break;
            case TcpRemoteClient::ErrorInvalidCurrencyPair:
                $message = 'Invalid currency pair';
                break;
            case TcpRemoteClient::ErrorCurrencyNotFound:
                $message = 'Currency not found';
                break;
            case TcpRemoteClient::ErrorCurrencyPairNotFound:
                $message = 'Currency pair not found';
                break;
            case TcpRemoteClient::ErrorCurrencyPairAlreadyExists:
                $message = 'Currency pair already exist';
                break;
            case TcpRemoteClient::ErrorStopLossUnavailable:
                $message = 'Stop loss unavailable';
                break;
            case TcpRemoteClient::ErrorTakeProfitUnavailable:
                $message = 'Take profit unavailable';
                break;
            case TcpRemoteClient::ErrorTrailingStopUnavailable:
                $message = 'Trailing stop unavailable';
                break;
            case TcpRemoteClient::ErrorUnknown:
                $message = 'Unknow core error';
                break;
        } 

        Response::ResponseError($message);
    }
    
}