<?php
return array(
    /* mysql database */
    'db' => array(
        'master'=> array(
            'host'  => '',
            'name'  => '',
            'user'  => '',
            'pwd'   => '',
            'port'  => 3306,
        ),
        'slave' => array(
            'host'  => '',
            'name'  => '',
            'user'  => '',
            'pwd'   => '',
            'port'  => 3306,
        ),
    ),

    /* po/mo */
    'mo'        => array(
        'zh-cn' => 'zh_CN',
        'zh-tw' => 'zh_TW',
        'ja-jp' => 'ja_JP',
        'ko-kr' => 'ko_KR',
        'de-de' => 'de_DE',
        'fr-fr' => 'fr_FR',
        'ru-ru' => 'ru_RU',
        'es-es' => 'es_ES',
    ),

    /* url */
    'url'       => array(
        'url_type'          => 'pathinfo',
    ),

    
);
