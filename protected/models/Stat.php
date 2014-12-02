<?php


class Stat extends CActiveRecord
{
    public static $indicatorOptions = [
        "usersRegisteredCountDefault", //число юзеров зареганых без партнера
        "usersRegisteredCountPartners", //число юзеров зареганых через партнера
        "usersRegisteredCountTotal", //общее число зареганых юзеров
        "moneyInputUSD", //всего USD введено в систему
        "moneyOutputUSD", //всего USD выведено из системы
        "moneyInputBTC", //всего BTC введено в систему
        "moneyOutputBTC", //всего BTC выведено из системы
        "tradesCountBuyUSDBTCTraders", //число сделок на покупку тикера USDBTC совершенных трейдерами
        "tradesCountBuyUSDBTCMakers", //число сделок на покупку тикера USDBTC совершенных мейкером
        "tradesCountSellUSDBTCTraders", //число сделок на продажу тикера USDBTC совершенных трейдерами
        "tradesCountSellUSDBTCMakers", //число сделок на продажу тикера USDBTC совершенных мейкером
        "tradesCountBuyTotalUSDBTC", //общее число сделок на покупку по тикеру USDBTC
        "tradesCountSellTotalUSDBTC", //общее число сделок на продажу по тикеру USDBTC
        "tradesVolumeUSDBTCUSD", //общий объем сделок в USD по тикеру USDBTC
        "tradesVolumeUSDBTCBTC", //общий объем сделок в BTC по тикеру USDBTC
        "commissionsInputEarnUSD", //сколько заработано с ввода USD
        "commissionsInputEarnBTC", //сколько заработано с ввода BTC
        "commissionsOutputEarnUSD", //сколько заработано с вывода USD
        "commissionsOutputEarnBTC", //сколько заработано с вывода BTC
        "commissionsTradesEarnUSD", //сколько заработано с торгов USD
        "commissionsTradesEarnBTC", //сколько заработано с торгов BTC
        "commissionsTradesPartnersSpendUSD", //сколько выплачено партнерам USD
        "commissionsTradesPartnersSpendBTC", //сколько выплачено партнерам BTC
        "commissionsTotalUSD", //?????????
        "commissionsTotalBTC", //????????
        "ordersCountBuyUSDBTC", //число оредров на покупку в тикере USDBTC
        "ordersCountSellUSDBTC", //число ордеров на продажу в тикере USDBTC
        "levelsCountBuyUSDBTC", //число уровней на покупку в тикере USDBTC
        "levelsCountSellUSDBTC", //число уровней на продажу в тикере USDBTC
        "liquidityBuyUSDBTCUSDTraders",
        "liquidityBuyUSDBTCUSDMakers",
        "liquiditySellUSDBTCBTCTraders",
        "liquiditySellUSDBTCBTCMakers",
        "liquidityBuyTotalUSDBTCUSD",
        "liquiditySellTotalUSDBTCBTC",
        "stockHighUSDBTCUSD",
        "stockSellUSDBTCUSD",
        "stockBuyUSDBTCUSD",
        "stockLowUSDBTCUSD",
        "stockSpreadUSDBTCUSD",
        "stockLastDealUSDBTCUSD",
        "cashTradersUSDTraders",
        "cashTradersUSDMakers",
        "cashTradersTotalUSD",
        "cashTradersBTCTraders",
        "cashTradersBTCMakers",
        "cashTradersTotalBTC",
        "cashGatewayTotalUSD",
        "cashReserveTotalUSD",
        "cashTotalUSD",
        "cashGatewayTotalBTC",
        "cashReserveTotalBTC",
        "cashTotalBTC",
        "cashRatioTotalUSD",
        "cashRatioTotalBTC"
    ];

    public static $intervalOptions = [
        '1m',
        '5m',
        '15m',
        '30m',
        '1h',
        '12h',
        '1d',
        '1d1ago',
        '1d2ago',
        '1d3ago',
        '1w',
        '1w1ago',
        '1w2ago',
        '1M',
        '1M1ago',
        '1q',
        '1q1ago',
    ];

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'stat';
    }

    public function rules()
    {
        return [
            ['timestamp', 'numerical', 'allowEmpty' => false, 'min' => 1, 'max' => PHP_INT_MAX, 'integerOnly' => true],
            ['value, indicator', 'length', 'allowEmpty' => false, 'min' => 1, 'max' => 255],
            //['indicator', 'in', 'allowEmpty' => false, 'range' => self::$indicatorOptions, 'strict' => true],

        ];
    }

    private static function getIntervalString($interval)
    {
        switch ($interval) {
            case '1m':
            {
                return '1 minute';
            }
            case '5m':
            {
                return '5 minute';
            }
            case '15m':
            {
                return '15 minute';
            }
            case '30m':
            {
                return '30 minute';
            }
            case '1h':
            {
                return '1 hour';
            }
            case '12h':
            {
                return '12 hour';
            }
            case '1d':
            {
                return '1 day';
            }
        }
        return null;
    }

    public static function getDataSlidingWindow($indicators, $dateFrom, $dateTo = null)
    {
        if (is_null($dateTo)) {
            $interval = $dateFrom;
            $timeTo = mktime(date("H"), date("i"), 0);
            $intervalString = self::getIntervalString($interval);
            if ($intervalString) {
                // -> 1 hour ago
                $timeFrom = strtotime(date('Y-m-d H:i:00', strtotime("$intervalString ago")));
            } else {
                $timeFrom = 0;
                $timeTo = 0;
            }
        } else {
            $timeFrom = strtotime($dateFrom);
            $timeTo = strtotime($dateTo);
        }

        $return = [];
        foreach ($indicators as $indicator) {
            $return[$indicator] = null;
        }

        if ($timeFrom) {
            $criteria = new CDbCriteria();
            $criteria->addInCondition('indicator', $indicators);
            $criteria->select = 'SUM(`value`) as `sum`, AVG(`value`) as `avg`, indicator';
            $criteria->addBetweenCondition('timestamp', $timeFrom, $timeTo);
            $criteria->group = 'indicator';

            $data = self::model()->commandBuilder->createFindCommand('stat', $criteria)->queryAll();
            foreach ($data as $row) {
                if (self::getIndicatorType($row['indicator']) === 'avg') {
                    $return[$row['indicator']] = $row['avg'];
                } else {
                    $return[$row['indicator']] = $row['sum'];
                }
            }
        }
        return $return;
    }

    private static function getIndicatorType($indicator)
    {
        if (
            strpos($indicator, 'orders') === 0 ||
            strpos($indicator, 'levels') === 0 ||
            strpos($indicator, 'liquidity') === 0 ||
            strpos($indicator, 'stock') === 0 ||
            strpos($indicator, 'cash') === 0
        ) {
            return 'avg';
        } else {
            return 'sum';
        }
    }

    public static function getDataHistory($indicator, $interval, $dateFrom, $dateTo)
    {
        $timeFrom = strtotime($dateFrom);
        $timeTo = strtotime($dateTo);
        if ($timeTo > TIME) {
            $timeTo = mktime(date("H"), date("i"), 0);
        }
        $intervalString = self::getIntervalString($interval);
        $steps = [];
        if ($intervalString && $timeFrom < $timeTo) {
            $begin = new DateTime(date('Y-m-d H:i:00', $timeFrom));
            $end = new DateTime(date('Y-m-d H:i:00', $timeTo));
            $dateInterval = DateInterval::createFromDateString($intervalString);
            $period = new DatePeriod($begin, $dateInterval, $end);
            foreach ($period as $dt) {
                $steps[] = (int)$dt->format('U');
            }
            $steps[] = (int)$end->format('U');
        }

        $return = [];


        if (self::getIndicatorType($indicator) == 'avg') {
            $aggregateFunction = 'AVG';
        } else {
            $aggregateFunction = 'SUM';
        }

        $timeFrom = null;
        $timeTo = null;
        foreach ($steps as $timestamp) {
            if (is_null($timeFrom)) {
                $timeFrom = $timestamp;
                continue;
            }
            $timeTo = $timestamp;

            $value = self::model()->dbConnection->createCommand("
                select $aggregateFunction(value)
                from stat
                where indicator = :indicator and timestamp >= :timeFrom and timestamp < :timeTo
            ")->queryScalar([
                    ':indicator' => $indicator,
                    ':timeFrom' => $timeFrom,
                    ':timeTo' => $timeTo
                ]);
            if (is_null($value)) {
                $value = null;
            }
            $return[] = [
                'timestamp' => $timeFrom,
                'value' => $value,
                'dateFrom' => date('Y-m-d H:i:s', $timeFrom),
                'dateTo' => date('Y-m-d H:i:s', $timeTo)
            ];

            $timeFrom = $timeTo;

        }
        return $return;
    }

    public static function getTotalRegCount()
    {
        return (int)\User::model()->count("type='trader'");
    }

    public static function getDirectRegCount()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('type', 'trader');
        $criteria->addCondition('refId IS NULL');
        return (int)\User::model()->count($criteria);
    }

    public static function getReferralRegCount()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('type', 'trader');
        $criteria->addCondition('refId IS NOT NULL');
        return (int)\User::model()->count($criteria);
    }

    public static function getVerifiedWaitingForModerationCount()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'waitingForModeration');
        return (int)\User::model()->count($criteria);
    }

    public static function getVerifiedWaitingForDocumentsCount()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'waitingForDocuments');
        return (int)\User::model()->count($criteria);
    }

    public static function getLastDayRegCount()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('type', 'trader');
        $criteria->addBetweenCondition('createdAt', self::_getLastDayTimeStamp(), self::_getNowTimestamp());
        return (int)\User::model()->count($criteria);
    }

    public static function getLastHourRegCount()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('type', 'trader');
        $criteria->addBetweenCondition('createdAt', self::_getLastHourTimeStamp(), self::_getNowTimestamp());
        return (int)\User::model()->count($criteria);
    }

    public static function getVerifiedRejectedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->addInCondition('verifiedStatus', self::_getVerifiedRejectedStatuses());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
            return (int)\VerifyLog::model()->count($criteria);
        } else {
            $criteria->compare('type', 'trader');
            return (int)\User::model()->count($criteria);
        }
    }

    public static function getVerifiedRejectedCountryCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'rejectedCountry');
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
            return (int)\VerifyLog::model()->count($criteria);
        } else {
            $criteria->compare('type', 'trader');
            return (int)\User::model()->count($criteria);
        }
    }

    public static function getVerifiedRejectedWrongCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'rejectedWrong');
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
            return (int)\VerifyLog::model()->count($criteria);
        } else {
            $criteria->compare('type', 'trader');
            return (int)\User::model()->count($criteria);
        }
    }

    public static function getVerifiedAcceptedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'accepted');
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
            return (int)\VerifyLog::model()->count($criteria);
        } else {
            $criteria->compare('type', 'trader');
            return (int)\User::model()->count($criteria);
        }
    }

    public static function getLastHourVerifiedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->addBetweenCondition('verifiedAt', self::_getLastHourTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastDayVerifiedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->addBetweenCondition('verifiedAt', self::_getLastDayTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastDayVerifiedAcceptedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'accepted');
        $criteria->addBetweenCondition('verifiedAt', self::_getLastDayTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastHourVerifiedAcceptedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'accepted');
        $criteria->addBetweenCondition('verifiedAt', self::_getLastHourTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastDayVerifiedRejectedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->addInCondition('verifiedStatus', self::_getVerifiedRejectedStatuses());
        $criteria->addBetweenCondition('verifiedAt', self::_getLastDayTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastDayVerifiedRejectedCountryCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'rejectedCountry');
        $criteria->addBetweenCondition('verifiedAt', self::_getLastDayTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastDayVerifiedRejectedWrongCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'rejectedWrong');
        $criteria->addBetweenCondition('verifiedAt', self::_getLastDayTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastHourVerifiedRejectedCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->addInCondition('verifiedStatus', self::_getVerifiedRejectedStatuses());
        $criteria->addBetweenCondition('verifiedAt', self::_getLastHourTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastHourVerifiedRejectedCountryCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'rejectedCountry');
        $criteria->addBetweenCondition('verifiedAt', self::_getLastHourTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getLastHourVerifiedRejectedWrongCount($verifiedBy = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('verifiedStatus', 'rejectedWrong');
        $criteria->addBetweenCondition('verifiedAt', self::_getLastHourTimeStamp(), self::_getNowTimestamp());
        if (!is_null($verifiedBy)) {
            $criteria->compare('verifiedBy', $verifiedBy);
        }
        return (int)\VerifyLog::model()->count($criteria);
    }

    public static function getPartnersTrade($userId, $currency, $interval)
    {
        $userPartnerIds = [];
        foreach (\User::model()->findAllByAttributes(['refId' => $userId]) as $user) {
            $userPartnerIds[] = $user->id;
        }
        if (!$userPartnerIds) {
            return '0';
        }

        $criteria = new \CDbCriteria();
        if (is_array($interval)) {
            $criteria->addBetweenCondition('createdAt', $interval[0], $interval[1]);
        }
        if ($currency == 'USD') {
            //оборот партнеров в USD (когда они продают биткоины)
            $criteria->addInCondition('userBuyId', $userPartnerIds);
            $criteria->select = 'SUM(size*price)';
        } elseif ($currency == 'BTC') {
            //оборот партнеров в BTC (когда они покупают биткоины)
            $criteria->addInCondition('userSellId', $userPartnerIds);
            $criteria->select = 'SUM(size)';
        }
        $value = \Deal::model()->dbConnection->commandBuilder->createFindCommand('deal', $criteria)->queryScalar();
        return is_null($value) ? '0' : $value;
    }

    public static function getPartnersUsers($userId, $interval, $onlyRegistered = false)
    {
        $criteria = new \CDbCriteria();
        $criteria->compare('refCodeUserId', $userId);
        if (is_array($interval)) {
            $criteria->addBetweenCondition('clickedAt', $interval[0], $interval[1]);
        }
        if ($onlyRegistered) {
            $criteria->addCondition('registeredUserId IS NOT NULL');
        }
        $criteria->select = 'count(*)';
        $value = \Yii::app()->db->commandBuilder->createFindCommand('partner_click', $criteria)->queryScalar();
        return is_null($value) ? '0' : $value;
    }

    public static function getPartnersEarn(\Account $account, $interval)
    {
        $criteria = new \CDbCriteria();
        $criteria->compare('accountId', $account->id);
        $criteria->select = 'SUM(debit)';
        if (is_array($interval)) {
            $criteria->addBetweenCondition('createdAt', $interval[0], $interval[1]);
        }
        $value = \Transaction::model()->dbConnection->commandBuilder->createFindCommand('transaction', $criteria)->queryScalar();
        return is_null($value) ? '0' : $value;
    }

    public static function getTransactionOrdersWaitingForOutput()
    {
        $criteria = new \CDbCriteria();
        $criteria->compare('status', 'waitForAccountant');
        $criteria->addInCondition('accountFromType', ['user.withdrawTrading', 'user.withdrawMerchant', 'user.withdrawWallet']);
        return \TransactionOrder::model()->count($criteria);
    }

    public static function getTransactionOrdersWaitingForInput()
    {
        $criteria = new \CDbCriteria();
        $criteria->compare('status', 'waitForAccountant');
        $criteria->addInCondition('accountFromType', ['system.gateway.internal', 'system.gateway.external.universe.unknown']);
        return \TransactionOrder::model()->count($criteria);
    }

    private static function _getVerifiedRejectedStatuses()
    {
        return [
            'rejected', 'rejectedCounty', 'rejectedWrong'
        ];
    }

    private static function _getLastHourTimeStamp()
    {
        return TIME - 1 * 3600;
    }

    private static function _getLastDayTimeStamp()
    {
        return TIME - 24 * 3600;
    }

    private static function _getLastWeekTimeStamp()
    {
        return TIME - 7 * 24 * 3600;
    }

    private static function  _getLastMonthTimeStamp()
    {
        return TIME - 30 * 24 * 3600;
    }

    private static function _getNowTimestamp()
    {
        return TIME;
    }
}