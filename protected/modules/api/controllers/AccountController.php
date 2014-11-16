<?php

namespace application\modules\site\controllers;

use Yii;

class AccountController extends \ApiController {

    public function filters() {
        return ['postOnly', 'auth'];
    }

    public function actionList() {
        $accounts = \Account::getByUser(\Yii::app()->params->userId);
        $userAccounts = [];
        foreach ($accounts as $account) {
            $userAccounts[$account->currency][$account->type] = $account;
        }

        $prepareAccount = function (\Account $account) {
            return [
                'accountId' => $account->publicId,
                'currency' => $account->currency,
                'balance' => $account->currency == 'BTC' ? bcadd($account->balance, '0', 8) : bcadd($account->balance, '0', 2),
                'status' => $account->status,
                'createdAt' => $account->createdAt,
                'type' => $account->type,
            ];
        };

        $currencies = ['USD', 'BTC'];
        $types = [
            'user.wallet',
            'user.merchant',
            'user.trading',
            'user.partnerCommission',
            'user.lockTrading',
            'user.withdrawWallet',
            'user.withdrawMerchant',
            'user.withdrawTrading',
        ];

        $json = [];

        //создадим недостающие аккаунты
        foreach ($currencies as $currency) {
            foreach ($types as $type) {
                if (!isset($userAccounts[$currency][$type])) {
                    $userAccounts[$currency][$type] = \Account::getOrCreateForUser(\Yii::app()->params->userId, $type, $currency);
                }
                $json[] = $prepareAccount($userAccounts[$currency][$type]);
            }
        }
        $this->json($json);
    }

    public function actionCreate() {
        $currency = \Yii::app()->request->getPost('currency');
        if (!in_array($currency, ['USD', 'EUR', 'RUR', 'BTC'])) {
            throw new \ModelException(['currency' => _('Wrong currency')]);
        }
        $account = \Account::getOrCreateForUser(\Yii::app()->params->userId, 'user.trading', $currency);
        $this->json([
            'id' => $account->guid,
            'publicId' => $account->publicId,
            'currency' => $account->currency,
            'balance' => $account->balance,
            'status' => $account->status,
            'createdAt' => $account->createdAt
        ]);
    }

    public function actionTransferFunds() {
        $accountFromId = \Yii::app()->request->getPost('accountFromId');
        $accountToId = \Yii::app()->request->getPost('accountToId');
        $amount = \Yii::app()->request->getPost('amount');

        $accountFrom = \Account::get($accountFromId);
        $accountTo = \Account::get($accountToId);

        if (!$accountFrom) {
            throw new \ModelException(_('Unknown accountFrom'));
        }
        if ($accountFrom->userId != \Yii::app()->params->userId) {
            throw new \ModelException(_('Account does not belong to current user'));
        }
        if (!$accountTo) {
            throw new \ModelException(_('Unknown accountTo'));
        }

        \Account::transferOwnFunds($accountFrom, $accountTo, $amount);

        $this->json(_('Transfer completed'));
    }

    public function actionGetDetails() {
        $accountId = \Yii::app()->request->getPost('accountId');
        $account = \Account::get($accountId);

        if (!$account || $account->userId != \Yii::app()->params->userId) {
            throw new \ModelException(_('Account not found'));
        }

        if (!in_array($account->type, ['user.trading', 'user.wallet'])) {
            throw new \ModelException(_('Wrong account type'));
        }

        switch ($account->currency) {
            case 'BTC':
                $address = \CoinAddress::create($account);
                $json = [
                    'type' => 'bitcoin',
                    'penaltyMax' => 0,
                    'penaltyFee' => 0,
                    'details' => [
                        'address' => $address->address
                    ]
                ];
                break;
            case 'USD':
                $json = [];
                $user = \User::get(\Yii::app()->params->userId);
                $gateway = \Gateway::getForPayment('bank.norvik', 'USD');
                if (!$gateway) {
                    throw new \ModelException(_('Gateway not found'));
                }
                $json['type'] = $gateway->type;
                $json['comment'] = "payment under the contract $account->publicId";
                $json['details'] = [
                    'Bank Name' => 'JSC «NORVIK BANKA», Riga, Latvia',
                    'SWIFT' => 'LATBLV22',
                    'Account' => 'LVXXXXXXXXXXXXXXXXXXXX',
                    'Beneficiary' => 'JOHN GALT LEGION LTD',
                    'Correspondent Bank Name' => 'Commerzbank AG, Frankfurt/Main, Germany',
                    'Correspondent SWIFT' => 'COBADEFF',
                    'Correspondent Account' => 'XXXXXXXXXXXXXX',
                ];
                $json['penaltyMax'] = 1000;
                $json['penaltyFee'] = 20;
                break;
            default:
                throw new \ModelException(_('Wrong currency'));
        }

        $json['account'] = \ArrayHelper::objectMap($account, ['accountId' => 'publicId', 'balance', 'type', 'currency']);
        $json['account']['holdAmount'] = 0;
        $this->json($json);
    }

    public function actionRequestCashOut() {
        $accountId = Yii::app()->request->getPost('accountId');
        $amount = Yii::app()->request->getPost('amount');
        $details = Yii::app()->request->getPost('details');

        $errors = [];
        $details = is_array($details) ? $details : json_decode($details, true);
        if (!is_array($details)) {
            $errors['details'] = _('Wrong format');
        }

        $account = \Account::get($accountId);
        if (!$account || $account->userId != Yii::app()->params->userId) {
            $errors['account'] = _('Account not found');
        }

        if ($errors) {
            throw new \ModelException(_('Validation failed'), $errors);
        }

        \TransactionOrder::requestCashOut($account, $amount, $details, Yii::app()->params->userId);

        $this->json(['accountId' => $accountId, 'amount' => $amount], _('Request accepted'));
    }

}
