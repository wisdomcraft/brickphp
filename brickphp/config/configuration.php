<?php
return array(
    /* mysql database */
    'db' => array(
        'master'=> array(
            'host'  =>'localhost',
            'name'  =>'',
            'user'  =>'root',
            'pwd'   =>'',
            'port'  =>3306,
        ),
        'slave' => array(
            'host'  =>'localhost',
            'name'  =>'',
            'user'  =>'root',
            'pwd'   =>'',
            'port'  =>3306,
        ),
    ),
    
    /* redis */
    'redis'     => array(
        'REDIS_ENABLE'      =>0,
        'REDIS_KEY_PREFIX'  =>'brick_',
        'REDIS_SERVER_ARRAY'=>array(
            '127.0.0.1:6379',
        ),
    ),
    
    /* rewrite */
    'rewrite'   => array(
        'rewrite_enabled'   => 0,
    ),
    
    /* url */
    'url'       => array(
        'url_type'          => 'native',    //native, pathinfo
    ),
);

