<?php

class UserLog extends CActiveRecord {

    private static $actions = [
        'login', // логин в систему
        'fundsAdded', // приход денег на аккаунт пользователя
        'fundsWithdrawalRequest', // запрос на вывод средств
        'fundsWithdrawal', // фактический вывод средств
        'fundsTransferred', // перевод средств
        'changePassword',
        'makeOrder',
        'cancelOrder',
        'makeConditional',
        'cancelConditional',
    ];
    
    private static $admin_actions = [
        'news',
        'accountVerified',
        'accountRejected',
        'accountLocked',
        'accountUnlocked',
        'accountRemoved',
        'gatewayFundsTransfer',
    ];
    
    public function rules() {
        return array(
            array('action', 'in', 'allowEmpty' => false, 'range' => array_merge(self::$actions, self::$admin_actions), 'strict' => true),
        );
    }
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'user_log';
    }

    
    public static function addAction($forUserId, $action, $message) {
        
        $ip = null;
        // при некоторых действиях лог IP не имеет смысла
        if (!in_array($action, ['orderPartialFilled', 'orderFilled', ''])) {
            $ip = Yii::app()->request->getUserHostAddress();
        }
        $log = new self();
        $log->userId = $forUserId;
        $log->action = $action;
        $log->data = $message;
        $log->ip = $ip;
        $log->createdAt = TIME;
        if (!$log->save()) {
            throw new ModelException('Log record was not created', $log->getErrors());
        }
        return true;
    }

    public static function getList(array $filters, array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = new CDbCriteria();
        if (isset($filters['userId'])) {
            $criteria->compare('userId', $filters['userId']);
        }
        
        if (isset($filters['action'])) {
            $criteria->compare('action', $filters['action']);
        }
        
        if (isset($filters['ip']) && !is_null($filters['ip'])) {
            $criteria->compare('ip', $filters['ip']);
        }
        
        $pagination['total'] = (int) self::model()->count($criteria);
        if ($limit) {
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        $sort = 'id DESC';
        $criteria->order = $sort;
        //ListCriteria::sortCriteria($criteria, $sort, ['id', 'createdAt']);
        
        return self::model()->findAll($criteria);
    }

    public static function getFakeData() {
        $account = \Account::getOrCreateForUser(\Yii::app()->params->userId, 'user.trading', 'USD');
        $accountTo = \Account::getOrCreateForUser(\Yii::app()->params->userId, 'user.wallet', 'USD');

        $data = [
            [
                'action' => 'login',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    'browser' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36',
                ],
            ],
            [
                'action' => 'logout',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    'browser' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36',
                ],
            ],
            [
                'action' => 'accountVerified',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [],
            ],
            [
                'action' => 'accountRejected',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    'verifiedReason' => 'bla bla bla',
                ],
            ],
            [
                'action' => 'fundsAdded',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    'accountId' => $account->publicId,
                    'type' => $account->type,
                    'currency' => $account->currency,
                    'balance' => $account->balance,
                    'amount' => mt_rand(100, 1000),
                ],
            ],
            [
                'action' => 'fundsWithdrawal',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    'accountId' => $account->publicId,
                    'balance' => $account->balance,
                    'type' => $account->type,
                    'currency' => $account->currency,
                    'amount' => mt_rand(100, 1000),
                ],
            ],
            [
                'action' => 'fundsTransferred',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    'accountFrom' => [
                        'accountId' => $account->publicId,
                        'balance' => $account->balance,
                        'type' => $account->type,
                        'currency' => $account->currency,
                    ],
                    'accountTo' => [
                        'accountId' => $accountTo->publicId,
                        'balance' => $accountTo->balance,
                        'type' => $accountTo->type,
                        'currency' => $accountTo->currency,
                    ],
                    'amount' => mt_rand(100, 1000),
                ],
            ],
            [
                'action' => 'orderCreated',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'LIMIT',
                        'side' => 'SELL',
                        'price' => 650,
                        'size' => 0.01,
                        'amount' => 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderCreated',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'LIMIT',
                        'side' => 'BUY',
                        'price' => 650,
                        'size' => 0.01,
                        'amount' => 650 * 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderCreated',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'MARKET',
                        'side' => 'BUY',
                        'price' => 650,
                        'size' => 0.01,
                        'amount' => 650 * 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderCreated',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'MARKET',
                        'side' => 'SELL',
                        'price' => 650,
                        'size' => 0.01,
                        'amount' => 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderFilled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'LIMIT',
                        'side' => 'SELL',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderFilled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'LIMIT',
                        'side' => 'BUY',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 650 * 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderFilled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'MARKET',
                        'side' => 'BUY',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 650 * 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderFilled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'MARKET',
                        'side' => 'SELL',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderCancelled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'LIMIT',
                        'side' => 'BUY',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 650 * 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderCancelled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'LIMIT',
                        'side' => 'SELL',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderCancelled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'MARKET',
                        'side' => 'BUY',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 650 * 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
            [
                'action' => 'orderCancelled',
                'ip' => '127.0.0.1',
                'createdAt' => time(),
                'data' => [
                    [
                        'id' => \Guid::generate(),
                        'type' => 'MARKET',
                        'side' => 'SELL',
                        'price' => 650,
                        'size' => 0.01,
                        'totalSpend' => 0,
                        'totalGet' => 0,
                        'amount' => 0.01,
                        'ticker' => 'USDBTC',
                    ]
                ]
            ],
        ];

        return $data;
    }

}
