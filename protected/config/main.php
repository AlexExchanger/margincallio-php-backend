<?php

return array(
    'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
    'name'=>'Exchange backend',

    'import'=>array(
        'application.modules.*',
        'application.components.*',
    ),

    'defaultController'=>'site',

    'components'=>array(
        'db' => array(
            'class' => 'DBConnection',
            'connectionString' => 'pgsql:host=localhost;port=5432;dbname=exchange',
            'username' => 'postgres',
            'password' => 'postgres',
            'charset' => 'utf8',
            'defaultSchema' => 'public',
        )
    ),
);