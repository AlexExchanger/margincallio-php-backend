<?php

return array(
    
    //Core params
    'coreUsdBtc' => array(
        'url' => '52.28.120.92',
        'port' => '1330',
    ),
    
    //Back-end params
    'registerByInvite' => false,
    'currentUrl' => 'http://stock.bit/',
    'supportedCurrency' => array('BTC', 'EUR', 'LTC', 'DOGE'),
    'currency' => array(
        array(
            'name' => 'BTC',
            'title' => 'Bitcoin',
            'symbol' => '฿'
        ),
        array(
            'name' => 'EUR',
            'title' => 'Euro',
            'symbol' => '€'
        ),
        array(
            'name' => 'LTC',
            'title' => 'Litecoin',
            'symbol' => 'Ł',
        ),
        array(
            'name' => 'DOGE',
            'title' => 'Dogecoin',
            'symbol' => 'Ð'
        ),
        ),
    'bitcoinService' => array(
        'url'=>'http://188.166.23.76'
    ),
    'withdrawalLimit' => array(
        'BTC' => '1',
    ),
);