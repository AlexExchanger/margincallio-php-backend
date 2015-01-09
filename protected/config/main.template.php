<?php

require dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'ExchangeException.php';

return array(
    'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
    'name'=>'Exchange backend',

    'import'=>array_merge(array(
        'application.components.*',
        'application.models.*',
    ), require 'import.php'),

    'defaultController'=>'site',
    
    'modules' => require 'modules.php',
    'components'=>array(
        'errorHandler'=>array(
            'errorAction'=>'site/error',
        ),
        'db' => array(
            'class' => 'CDbConnection',
            'connectionString' => 'pgsql:host=127.0.0.1;port=5432;dbname=exchange_stock',
            'emulatePrepare' => true,
            'username' => 'admin',
            'password' => '1',
            'charset' => 'utf8',
        ),
        'urlManager' => array(
            'urlFormat'=>'path',
            'rules' => array(
                '<module:\w+>/<controller:\w+>/<action:[0-9a-zA-Z_\-]+>/<id:[0-9a-zA-Z_\-]+>' => '<module>/<controller>/<action>',
                '<module:\w+>/<controller:\w+>/<action:[0-9a-zA-Z_\-]+>'          => '<module>/<controller>/<action>',
                '<module:\w+>/<controller:\w+>'                                   => '<module>/<controller>/index',
                '<controller:\w+>/<action:[0-9a-zA-Z_\-]+>'                       => '<controller>/<action>',
                '<controller:\w+>'                                                => '<controller>/index',
            ),
        ),
    ),
    'params' => array_merge(array(
        'pathToLog' =>  implode(DIRECTORY_SEPARATOR, array($_SERVER['DOCUMENT_ROOT'], 'logs')),
    ), require 'params.php'),
);