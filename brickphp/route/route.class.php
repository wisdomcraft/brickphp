<?php
namespace brick;
class route{
    
    
    public function index(){
        $url        = config('url');
        $url_type   = $url['url_type'];
        if(!in_array($url_type,['native','pathinfo']))
            die("{\"code\":0, \"message\":\"url type is incorrect, only native and pathinfo allowed, current is {$url_type}\"}");
        if($url_type === 'native'){
            $_action        = !empty($_GET['_action'])?strtolower($_GET['_action']):'index/index';
        }else{
            if(is_null(@$_SERVER['PATH_INFO']))
                die("{\"code\":0, \"message\":\"PATH_INFO null in \$_SERVER, you need set php.ini and http server to support php PATH_INFO\"}");
            $_action        = $_SERVER['PATH_INFO'];
            if(strlen($_action) === 0) $_action = '/index/index';
            
        }
        
        if(stripos($_action, '/') === 0)        $_action = substr($_action, 1);
        if(stripos($_action, '/') === false)    $_action = $_action . '/index';
        $_action        = strtolower($_action);
        $_action_array  = explode('/', $_action);
        
        $_action_array2     = $_action_array;
        array_pop($_action_array2);
        array_pop($_action_array2);
        array_unshift($_action_array2, 'controller');
        
        $NAMESPACE          = implode('\\', $_action_array2);
        
        $count              = count($_action_array);
        $CONTROLLER         = $_action_array[$count-2];
        if(in_array($CONTROLLER, ['final','interface','implements','finally']))
            die("{\"code\":0, \"message\":\"controller name not allowed, {$CONTROLLER}\"}");
        
        $CONTROLLER_FILE    = APP_PATH . implode('/', $_action_array2) . "/{$CONTROLLER}.controller.php";
        if(!file_exists($CONTROLLER_FILE))
            die("{\"code\":0, \"message\":\"controller file not exist, {$CONTROLLER_FILE}\"}");

        $FUNCTION   = end($_action_array);
        
        $CLASS      = "{$NAMESPACE}\\{$CONTROLLER}";
        if(!class_exists($CLASS)) require $CONTROLLER_FILE;
        if(!class_exists($CLASS))
            die("{\"code\":0, \"message\":\"class not exists, check controller file, {$CONTROLLER_FILE}\"}");
        
        $OBJECT     = new $CLASS;
        
        if(method_exists($OBJECT, '__global'))  $OBJECT->__global();    //global controller variable for assign()
        
        if(method_exists($OBJECT, $FUNCTION)){
            $OBJECT->$FUNCTION();
        }else{
            $result['code']     = 0;
            $result['message']  = "the function '{$FUNCTION}' of controller class '{$CLASS}' not exist!";
            die(json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        
        
        /*
        $GROUP      = !empty($_GET['g'])?strtolower($_GET['g']):'home';
        $CONTROLLER = !empty($_GET['c'])?strtolower($_GET['c']):'index';
        $ACTION     = !empty($_GET['a'])?strtolower($_GET['a']):'index';

        if(file_exists(APP_PATH."core/{$GROUP}/controller/{$CONTROLLER}.controller.php")){
            require APP_PATH."core/{$GROUP}/controller/{$CONTROLLER}.controller.php";
        }else{
            die("{\"status\":\"error\", \"description\":\"the '{$GROUP}/controller/{$CONTROLLER}' controller file not exist!\"}");
        }
        
        if(class_exists("{$GROUP}\\controller\\{$CONTROLLER}")){
            $OBJECT_NAME    = "{$GROUP}\\controller\\{$CONTROLLER}";
            $OBJECT         = new $OBJECT_NAME;
        }else{
            die("{\"status\":\"error\", \"description\":\"the object '{$GROUP}\\controller\\{$CONTROLLER}' not exist!\"}");
        }
        
        if(file_exists(APP_PATH."core/{$GROUP}/controller/__global.controller.php")){
            require APP_PATH."core/{$GROUP}/controller/__global.controller.php";
            if(class_exists("{$GROUP}\\controller\\__global")){
                $GLOBAL_GROUP_OBJECT_NAME   = "{$GROUP}\\controller\\__global";
                $GLOBAL_GROUP_OBJECT        = new $GLOBAL_GROUP_OBJECT_NAME;
                if(method_exists($GLOBAL_GROUP_OBJECT, '__global'))
                    $GLOBAL_GROUP_OBJECT->__global();
            } 
        }

        if(method_exists($OBJECT, $ACTION)){
            $OBJECT->$ACTION();
        }else{
            die("{\"status\":\"error\", \"description\":\"the function '$ACTION' of controller '{$CONTROLLER}' not exist!\"}");
        }
        */
    }
    
    
    
}

