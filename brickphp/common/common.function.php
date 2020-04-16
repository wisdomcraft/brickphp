<?php


/*
* $object = new controller_class_name
* use other controller class, even not in group
*/
function A($name){
    if(strlen($name) == 0) 
        die("{\"status\":\"error\", \"description\":\"A() function is wrong, its parameter is empty!\"}");
    
    $array      = explode('/', $name);
    if(count($array) > 2)
        die("{\"status\":\"error\", \"description\":\"A('{$name}') function is wrong, its parameter is too more!\"}");
    
    if(count($array) == 1){
        $class  = N('GROUP_NAME').'\controller\\'.$array[0];
        $file   = APP_PATH.'core/'.N('GROUP_NAME').'/controller/'.$array[0].'.controller.php';
    }elseif(count($array) == 2){
        $class  = $array[0].'\controller\\'.$array[1];
        $file   = APP_PATH.'core/'.$array[0].'/controller/'.$array[1].'.controller.php';
    }
    
    if(!file_exists($file))
        die("{\"status\":\"error\", \"description\":\"file is not exists by A('{$name}'), {$file}\"}");
    
    if(!class_exists($class)) require $file;
    
    $object = new $class;
    return $object;
}


/*
* get config value
*/
function config($config_key){
    if(!file_exists(LIBRARY_PATH.'config/configuration.php'))
        die('error, config file is not exist in framework! brickphp/common/functions.php #39');
    $config_frame   = require(LIBRARY_PATH.'config/configuration.php');

    if(!file_exists(APP_PATH.'config/config.php'))
        return @$config_frame[$config_key];
    $config_app     = require(APP_PATH.'config/config.php');

    foreach($config_app as $key=>$vo){
        if(is_array($vo)){
            foreach($vo as $key2=>$vo2){
                if(is_array($vo2)){
                    foreach($vo2 as $key3=>$vo3){
                        if(is_array($vo3)){
                            $config_frame[$key][$key2][$key3] = $vo3;   //need go on recursive here
                        }else{
                            $config_frame[$key][$key2][$key3] = $vo3;
                        }
                    }
                }else{
                    $config_frame[$key][$key2] = $vo2;
                }
            } 
        }else{
            $config_frame[$key] = $vo;
        }
    }
    
    return @$config_frame[$config_key];
}


function C($config_key){
    return config($config_key);
    
}


/*
* object about mysqli
*/
function D($host='slave'){
    if(!in_array($host, array('master', 'slave'))) die('error, parameter in D() function must be master, slave or empty!');
    $object = new \brick\mysql;
    $object->host($host);
    return $object;
}


/*
* 
*/
function model($model){
	if(substr_count($model, '/') === 0){
		$group  = N('GROUP_NAME');
		$name	= $model;
	}elseif(substr_count($model, '/') === 1){
		list($group, $name) = explode('/', $model);
	}else{
		die('error, model() function parameter is incorrect: '.$model);
	}
    
    $class  = "\\{$group}\model\\{$name}";
    $file   = APP_PATH . "core/{$group}/model/{$name}.model.php";
    if(!file_exists($file)) die("error, file not exist, {$file}");
    if(!class_exists($class)) require $file;
    $object = new $class;
    return $object;
}

function M($name){
	return model($name);
}

/*
* custom function N, it is to get current controller and action name
*/
function N($name){
    if($name == 'GROUP_NAME'){
        $GROUP      = !empty($_GET['g'])?strtolower($_GET['g']):'home';
        return $GROUP;
    }elseif($name == 'CONTROLLER_NAME'){
        $CONTROLLER = !empty($_GET['c'])?strtolower($_GET['c']):'index';
        return $CONTROLLER;
    }elseif($name == 'ACTION_NAME'){
        $ACTION     = !empty($_GET['a'])?strtolower($_GET['a']):'index';
        return $ACTION;
    }else{
        return '';
    }
}


/*
* custom function U, it is about url
* we can use it in controller and template
*/
function url($_action, $array=false){
    $rewrite    = C('rewrite');
    if($rewrite['rewrite_enabled']===1 && !is_null(@$rewrite[$argument])){
        if($array!== false && is_array($array) && count($array)>0){
            $i = 1;
            foreach($array as $key=>$arr){
                if(strpos($rewrite[$argument], '$'.$i) >= 0) $rewrite[$argument] = str_replace('$'.$i, $arr, $rewrite[$argument]);
                $i++;
            }
        }
        
        return $rewrite[$argument];
    }
    
    if(stripos($_action, '/') !== 0) $_action = "/{$_action}";
    
    $url        = $_SERVER['SCRIPT_NAME'];
    $url        .= "?_action={$_action}";
    
    if($array!==false && is_array($array)){
        foreach($array as $key=>$arr){
            $url .= "&{$key}={$arr}";
        }
    }
    
    return $url;
}


function U($argument, $array=false){
    return url($argument, $array);
}


//dump, output array for test
function dump($array){
    if(is_array($array)){
        $content = "<pre>\n";
        $content .= htmlspecialchars(print_r($array,true));
        $content .= "\n</pre>\n";
        echo $content;
    }else{
        var_dump($array);
    }
}




//require file
function R($file){
    if(file_exists($file)){
        return require $file;
    }else{
        die("error, '{$file}' this file is not exists!");
    }
}


//require file in template
function REQUIRE_TEMPLATE($name){
    $array = explode('/', $name);
    if(count($array)==1){
        $file = APP_PATH.'core/'.N('GROUP_NAME').'/view/'.N('CONTROLLER_NAME').'/'.$array[0].'.htm';
    }elseif(count($array)==2){
        $file = APP_PATH.'core/'.N('GROUP_NAME').'/view/'.$array[0].'/'.$array[1].'.htm';
    }elseif(count($array)==3){
        $file = APP_PATH.'core/'.$array[0].'/view/'.$array[1].'/'.$array[2].'.htm';
    }else{
        die('{"status":"error", "description":"parameter is wrong in REQUIRE_TEMPLATE(), brickphp/common/functions.php 126#"}');
    }

    if(!file_exists($file)){
        die('{"status":"error", "description":"file is not exists by REQUIRE_TEMPLATE(), brickphp/common/functions.php 130#"}');
    }
    
    require $file;
}


//load language file
function L($file=NULL, $directory=NULL){
    if(is_null($file))                                          die('error, file parameter in L() function cannot empty or NULL!');
    
    $language_host      = C('host');
    $language_host      = array_filter($language_host);
    $host_language      = array_flip($language_host);
    $host               = $_SERVER['SERVER_NAME'];
    if(@is_null($host_language[$host]))                         die('error, config about language and host is wrong!');
    $language           = $host_language[$host];
    
    $group_name         = N('GROUP_NAME');
    $controller_name    = N('CONTROLLER_NAME');
    $filepath           = APP_PATH."Language/{$group_name}/{$controller_name}/{$file}_{$language}.php";
    if(!file_exists($filepath))                                 die("error, language file is not exists, file: {$filepath}");

    $language_array     = require $filepath;
    if(!is_array($language_array))                              die('error, get language content failed, it is not array');
    
    return $language_array;
}








