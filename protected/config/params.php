<?php

return array(
    
    //Core params
    'coreUsdBtc' => array(
        'url' => '184.168.134.144',
        'port' => '1330',
    ),
    
    //Back-end params
    'registerByInvite' => false,
    'currentUrl' => 'http://site.exchange/',
    'supportedCurrency' => array('BTC', 'USD', 'EUR'),
    'currency' => array(
        array(
            'name' => 'BTC',
            'title' => 'Bitcoin',
            'symbol' => '฿'
        ),
        array(
            'name' => 'USD',
            'title' => 'United states dollar',
            'symbol' => '$'
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