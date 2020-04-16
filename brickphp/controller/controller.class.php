<?php
namespace brick;
class controller{
    
    
    private static $templateVar = array();


    /*
    * assign, transfer data to this class object
    */
    protected function assign($valueName, $value){
        $array = self::$templateVar;
        if(!is_null(@$array[$valueName]))   die("variable repeated in custom function assign('{$valueName}', ...)");
        
        $array[$valueName]  = $value;
        self::$templateVar  = $array;
    }


    /*
    * display and relation functions
    */    
    protected function display($tpl=false){
        $url        = config('url');
        $url_type   = $url['url_type'];
        if(!in_array($url_type,['native','pathinfo']))
            die("{\"code\":0, \"message\":\"url type is incorrect, only native and pathinfo allowed, current is {$url_type}, " . __FILE__ . ", #" . __LINE__ . "\"}");
        if($url_type === 'native'){
            $_action    = !empty($_GET['_action'])?strtolower($_GET['_action']):'index/index';
        }else{
            if(is_null(@$_SERVER['PATH_INFO']))
                die("{\"code\":0, \"message\":\"PATH_INFO null in \$_SERVER, you need set php.ini and http server to support php PATH_INFO, " . __FILE__ . ", #" . __LINE__ . "\"}");
            $_action    = $_SERVER['PATH_INFO'];
            if(strlen($_action) === 0) $_action = '/index/index';
        }

        if(stripos($_action, '/') === 0)        $_action = substr($_action, 1);
        if(stripos($_action, '/') === false)    $_action = $_action . '/index';
        $_action            = strtolower($_action);
        $_action_array      = explode('/', $_action);
        
        if($tpl === false){
            $_action_array2 = $_action_array;
            array_pop($_action_array2);
            array_unshift($_action_array2, 'view');
            $function       = end($_action_array);
            $tpl            = APP_PATH . implode('/', $_action_array2) . "/{$function}.htm";
        }elseif(stripos($tpl,'/') === false){
            $_action_array2 = $_action_array;
            array_pop($_action_array2);
            array_unshift($_action_array2, 'view');
            $function       = end($_action_array);
            $tpl            = APP_PATH . implode('/', $_action_array2) . "/{$tpl}.htm";
        }elseif(stripos($tpl,'/') === 0){
            $tpl            = APP_PATH . "view{$tpl}.htm";
        }else{
            $_action_array2 = $_action_array;
            array_pop($_action_array2);
            array_unshift($_action_array2, 'view');
            $function       = end($_action_array);
            $tpl            = APP_PATH . implode('/', $_action_array2) . "/{$tpl}.htm";
        }
        
        echo $this->__display_load($tpl, $_action);
    }
    
    
    protected function __display_load($tpl, $_action){
        $cache = $this->__display_load_check($tpl, $_action);
        
        ob_start();
        
        extract(self::$templateVar);
        
        require $cache;
        $content = ob_get_contents();
        
        ob_end_clean();
        
        return $content;
    }
    
    
    protected function __display_load_check($tpl, $_action){
        if(!file_exists($tpl)) die("{\"code\":0, \"message\":\"error, this template file is not exist, {$tpl}, " . __FILE__ . ", #" . __LINE__ . "\"}"); 
        
        $_action_array  = explode('/', $_action);
        array_pop($_action_array);
        $_action        = implode('/', $_action_array);
        
        $cache          = APP_PATH . 'runtime/cache/' . $_action . '/' . md5_file($tpl) . '.php';
        if(!file_exists($cache) || filemtime($tpl)>=filemtime($cache)){
            if(!is_dir(APP_PATH.'runtime/cache/'.$_action))   mkdir(APP_PATH.'runtime/cache/'.$_action, 0777, true);
            $content    = file_get_contents($tpl);
            $content    = $this->__label_handle($content);
            $file       = fopen($cache, 'w+');
            fwrite($file, $content);
            fclose($file);
        }
        
        return $cache;
    }

    
    protected function __label_handle($content){
        preg_match_all('/<include file="(.*)" \/>/isU', $content, $match);
        
        $label      = $match[0];
        $path       = $match[1];
        $string     = array();
        
        for($i=0;$i<count($path);$i++):
            if(strlen($path[$i]) == 0){
                $result['code']         = 0;
                $result['description']  = '<include /> label is wrong, parameter is empty!';
                die(json_encode($result));
            }
            
            $array = explode('/', $path[$i]);
            if(count($array) > 3){
                $result['code']         = 0;
                $result['description']  = '<include /> label is wrong, "/" is too many!';
                die(json_encode($result));
            }
            if(count($array) == 1){
                $file = APP_PATH.'core/'.N('GROUP_NAME').'/view/'.N('CONTROLLER_NAME').'/'.$array[0].'.htm';
            }elseif(count($array)==2){
                $file = APP_PATH.'core/'.N('GROUP_NAME').'/view/'.$array[0].'/'.$array[1].'.htm';
            }elseif(count($array)==3){
                $file = APP_PATH.'core/'.$array[0].'/view/'.$array[1].'/'.$array[2].'.htm';
            }
            
            if(!file_exists($file)){
                $result['code']         = 0;
                $result['description']  = "<include /> label file '{$file}' is not exist!";
                die(json_encode($result));
            }
            
            $string[] = file_get_contents($file);

            unset($file);
            unset($array);
        endfor;
        
        return str_replace($label, $string, $content);
    }
    
    
    protected function success($content='', $url=NULL){
        require __DIR__ . '/template/success.htm';
        exit;
    }


    protected function error($content='', $url=NULL){
        require __DIR__ . '/template/error.htm';
        exit;
    }
    
    
}

