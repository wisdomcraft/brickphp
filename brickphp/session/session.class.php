<?php
namespace brick;
class session{
    
    
    private static $config_redis    = array();
    private static $config_mysql    = array();
    private static $mysql_connect   = null;
    private static $session_table   = 'sess_session';
    private static $session_name    = 'BRICKSESSID';
 
    
    private static function __init(){
        if(!file_exists(LIBRARY_PATH.'config/configuration.php'))   die('error, config file is not exist in framework!');
        $config_frame   = require(LIBRARY_PATH.'config/configuration.php');

        if(!file_exists(APP_PATH.'config/config.php'))              die('error, config file is not exist in application!');
        $config_app     = require(APP_PATH.'config/config.php');

        if(@is_null($config_app['redis'])){
            self::$config_redis['REDIS_ENABLE'] = 0;
        }else{
            self::$config_redis                 = array_merge($config_frame['redis'], $config_app['redis']);
        }
        
        if(@is_null($config_app['db']))                              die('error, db config value is null in config file of application!');

        self::$config_mysql                     = array_merge($config_frame['db'], $config_app['db']);
    }
    
    
    //get a GUID value
    private static function __guid(){
        $unique = microtime() . @implode(',', $_SERVER) . rand(0, 99999);
        $md5    = md5($unique);
        $guid   = substr($md5, 8, 16);
        return $guid;
    }
    
    
    //get current url's top domain
    private static function __domain(){
        $server_name    = $_SERVER['SERVER_NAME'];
        if(substr_count($server_name, '.') === 0) die('error, website server name(domain) is uncorrect from file loginController.class.php');
        if(substr_count($server_name, '.') === 1) return $server_name;
        
        $domain_suffix  = substr($server_name, strrpos($server_name, '.'));      
        $domain_part    = substr($server_name, 0, strrpos($server_name, '.'));
        $domain_part2   = substr($domain_part, strrpos($domain_part, '.')+1);
        $domain         = $domain_part2.$domain_suffix;
        return $domain;
    }
    
    
    //set or get session id
    public static function session_id($value=null){
        if(strlen($value) === 0){
            $session_name   = self::$session_name;
            $session_cookie = @$_COOKIE[$session_name];
            if(is_null($session_cookie)){
                $session_id = self::__guid();
                $domain     = self::__domain();
                setcookie($session_name, $session_id, time()+60*60*24*30, '/', $domain);
            }else{
                $session_id = $session_cookie;
            }
        }else{
            $session_id = $value;
        }
        
        return $session_id;
    }
    
    
    //set session, add new session or update existed session
    public static function session_set($session_key, $session_value, $session_id=null){
        if(strlen($session_id) === 0) $session_id = self::session_id();
        
        $session_value_base64   = base64_encode($session_value);
        $time                   = time();
        
        if(count(self::$config_redis) === 0) self::__init();
        
        $config_redis   = self::$config_redis;
        $session_table  = self::$session_table;
        
        $existed_sql    = "select session from {$session_table} where `session_id`='{$session_id}'";
        $existed_data   = self::select_mysql($existed_sql);
        if(is_null($existed_data)){
            $session    = "{$session_key}:{$session_value_base64};";
            $sql        = "INSERT INTO `{$session_table}`(`session_id`, `session`, `update_time`, `lastread_time`) VALUE('{$session_id}', '{$session}', '{$time}', '{$time}')";
            self::insert_mysql($sql);
        }else{
            $existed_session_array  = explode(';', $existed_data['session']);
            $tmp_array  = array();
            foreach($existed_session_array as $element){
                if(strlen(trim($element)) > 0){
                    $element_array  = explode(':', $element);
                    $tmp_array[$element_array[0]]   = $element_array[1];
                }
            }
            
            if(!is_null(@$tmp_array[$session_key]))
                if($tmp_array[$session_key] === $session_value_base64) return true;
            
            $tmp_array2[$session_key]   = $session_value_base64;
            $tmp_array3 = array_merge($tmp_array, $tmp_array2);
            
            $session    = '';
            foreach($tmp_array3 as $_key=>$_value){
                $session    .= $_key;
                $session    .= ':';
                $session    .= $_value;
                $session    .= ';';
            }
            
            $sql        = "UPDATE `{$session_table}` SET `session`='{$session}', `update_time`='{$time}' WHERE `session_id`='{$session_id}'";
            self::update_mysql($sql);
        }
        
        if($config_redis['REDIS_ENABLE'] == 1){
            $obj        = new \RedisArray($config_redis['REDIS_SERVER_ARRAY']);
            $redis_key  = $config_redis['REDIS_KEY_PREFIX']."session_{$session_id}";
            $obj->set($redis_key, $session);
        }
        
        return true;
    }
    
    
    //get session
    public static function session_get($session_key, $session_id=null){
        if(strlen($session_id) === 0){
            $session_name   = self::$session_name;
            $session_cookie = @$_COOKIE[$session_name];
            if(is_null($session_cookie)) return NULL;
            $session_id     = $session_cookie;
        }
    
        if(count(self::$config_redis) === 0) self::__init();
        
        
        $config_redis   = self::$config_redis;
        $session_table  = self::$session_table;
        
        if($config_redis['REDIS_ENABLE'] == 1){
            $obj        = new \RedisArray($config_redis['REDIS_SERVER_ARRAY']);
            $redis_key  = $config_redis['REDIS_KEY_PREFIX']."session_{$session_id}";
            if($obj->exists($redis_key)){
                $value  = $obj->get($redis_key);
            }else{
                $sql    = "select * from {$session_table} where `session_id`='{$session_id}'";
                $data   = self::select_mysql($sql);
                if(is_null($data) || strlen(trim($data['session']))===0){
                    return null;
                }else{
                    $value  = $data['session'];
                    $obj->set($redis_key, $value);
                }
            }
        }else{
            $sql    = "select * from {$session_table} where `session_id`='{$session_id}'";
            $data   = self::select_mysql($sql);
            if(is_null($data) || strlen(trim($data['session']))===0)  return null;
            $value  = $data['session'];
            $time   = time();
            $sql2   = "update {$session_table} set `lastread_time`='{$time}' where `session_id`='{$session_id}'";
            self::update_mysql($sql2);
        }
        
        $session_array  = explode(';', $value);
        $tmp_array      = array();
        foreach($session_array as $element){
            if(strlen(trim($element)) > 0){
                $element_array  = explode(':', $element);
                $tmp_array[$element_array[0]]   = $element_array[1];
            }
        }

        $session_value_base64   = @$tmp_array[$session_key];
        if(is_null($session_value_base64)) return null;
        
        $session_value  = base64_decode($session_value_base64);
        return $session_value;
    }
    
    
    //delete all session value of a session_id
    public static function session_delete($session_id=null){
        if(strlen($session_id) === 0){
            $session_name   = self::$session_name;
            $session_cookie = @$_COOKIE[$session_name];
            if(is_null($session_cookie)) return true;
            $session_id     = $session_cookie;
        }
        
        if(count(self::$config_redis) === 0) self::__init();

        $config_redis   = self::$config_redis;
        $session_table  = self::$session_table;
        
        $sql            = "DELETE FROM {$session_table} WHERE `session_id`='{$session_id}'";
        $data           = self::delete_mysql($sql);
        if(!$data)      return false;
        
        if($config_redis['REDIS_ENABLE'] == 1){
            $obj        = new \RedisArray($config_redis['REDIS_SERVER_ARRAY']);
            $redis_key  = $config_redis['REDIS_KEY_PREFIX']."session_{$session_id}";
            $obj->delete($redis_key);
        }
        
        return true;
    }
    
    
/*
* mysql
*/
    //return null if data not exist
    //return array if data exist, include select only one column
    private static function select_mysql($sql){
        $result = self::query_mysql($sql);
        $data   = mysqli_fetch_assoc($result);
        return $data;
    }
    
    
    private static function insert_mysql($sql){
        $result = self::query_mysql($sql);
        return $result;
    }
    
    
    private static function update_mysql($sql){
        $result = self::query_mysql($sql);
        return $result;
    }
    
    
    private static function delete_mysql($sql){
        $result = self::query_mysql($sql);
        return $result;
    }
    
    
    private static function query_mysql($sql){
        $config_mysql   = self::$config_mysql;
        
        $config             = array();
        $config['host']     = $config_mysql['master']['host'];
        $config['username'] = $config_mysql['master']['user'];
        $config['password'] = $config_mysql['master']['pwd'];
        $config['port']     = $config_mysql['master']['port'];
        $config['database'] = $config_mysql['master']['name'];
        
        @$mysqli    = new \mysqli($config['host'],$config['username'],$config['password'],$config['database'],$config['port']);
        if($mysqli->connect_error){
            $error  = $mysqli->connect_error;
            $mysqli->close();
            die($error.', session.class.php:257');
        }      
        
        $mysqli->query("SET NAMES 'utf8'");
        $result     = $mysqli->query($sql);
        if(strlen($mysqli->error) > 0){
            $error  = $mysqli->error;
            $mysqli->close();
            die($error.', session.class.php:265');
        }
        
        $mysqli->close();
        
        return $result;
    }

    
}

