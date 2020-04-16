<?php
function _e($string, $domain){
    $object = new \brick\mo;
    
    if(!is_string($string) || strlen($string)===0){
        echo $string;
        $object->log('error, $string is not string or empty, mo.php:7');
        return false;
    }
    
    if(!is_string($domain) || strlen($domain)===0){
        echo $string;
        $object->log('error, $domain is not string or empty, mo.php:13');
        return false;
    }
    
    $host           = $_SERVER['HTTP_HOST'];
    list($name, )   = explode('.', $host, 2);
    $language       = 'en_US';
    if($name !== 'www'){
        $mo_config  = C('mo');
        if(is_null($mo_config[$name])){
            echo $string;
            $object->log("error, language parameter does not exist in url domain, {$name}, mo.php:24");
            return false;
        }
        $language   = $mo_config[$name];
    }
    
    if($language === 'en_US'){
        echo $string;
        return false;
    }
    
    $object->language = $language;
    $object->domain   = $domain;
    $object->main($string);    
}