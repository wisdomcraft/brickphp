<?php
namespace brick;
class mysqli{


/*
* __construct()
*
* host()
*
* close()
* 
* count()
*   query()
*       __connect()
*
* select()
*   query()
*       __connect()
*
* find()
*   query()
*       __connect()
*
* insert()
*   __connect()
*
* arrayToUpdateSql()
*
* update()
*   __connect()
*
* delete()
*   query()
*       __connect()
*
* string2hex()
*
* __destruct()
*/

    private $config         = array();
    private $host           = 'slave';
    private $close          = true;
    private $connect_master = NULL;
    private $connect_slave  = NULL;
    
    
    
    public function __construct(){
        if(!file_exists(LIBRARY_PATH.'config/configuration.php'))   die('error, config file is not exist in framework!');
        $config_frame   = require(LIBRARY_PATH.'config/configuration.php');

        if(!file_exists(APP_PATH.'config/config.php'))              die('error, config file is not exist in application!');
        $config_app     = require(APP_PATH.'config/config.php');

        if(@is_null($config_app['db']))                             die('error, db config value is null in config file of application!');

        $this->config   = array_merge($config_frame['db'], $config_app['db']);
        
        return $this;
    }
    
    
    public function host($host='slave'){
        if(!in_array($host, array('master', 'slave')))              die('error, host value must be master or slave! mysqli.class.php:61');
        $this->host = $host;
        return $this;
    }
    
    
    public function close($status=null){
        if($status === false || $status === '0' || $status === 0) $this->close = false;
        return $this;
    }
    
    
    public function close_no(){
        $this->close = false;
        return $this;
    }
    
    
    private function __connect(){
        $config_all         = $this->config;
        $host               = $this->host;
        $config['host']     = $config_all[$host]['host'];
        $config['username'] = $config_all[$host]['user'];
        $config['password'] = $config_all[$host]['pwd'];
        $config['database'] = $config_all[$host]['name'];
        $config['port']     = $config_all[$host]['port'];
        
        @$connect           = new \mysqli($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
        $connect_error      = $connect->connect_error;
        if($connect_error){
            $connect = NULL;
			die("{\"code\":0, \"message\":\"{$connect_error}, mysqli.class.php #90\"}");
        }
        
        if($host === 'master'){
            $this->connect_master   = $connect;
        }elseif($host === 'slave'){
            $this->connect_slave    = $connect;
        }

        return $this;
    }
    
    
    public function query($sql){
        $host           = $this->host;
        if($host === 'master'){
            if(is_null($this->connect_master))  $this->__connect();
            $connect    = $this->connect_master;
        }elseif($host === 'slave'){
            if(is_null($this->connect_slave))   $this->__connect();
            $connect    = $this->connect_slave;
        }
        
        $connect->query("SET NAMES 'utf8'");
        $result         = $connect->query($sql);
        
        $connect_error  = $connect->error;
        if(strlen($connect_error) > 0){
            $connect->close();
            if($host === 'master')  $this->connect_master = NULL;
            if($host === 'slave')   $this->connect_slave  = NULL;
            die("{\"code\":0, \"message\":\"{$connect_error}, {$sql}, mysqli.class.php #128\"}");
        }
        
        $this->host     = 'slave';
        
        return $result;
    }
    
    
    //return intger number
    public function count($sql){
        $list   = explode(' ', $sql);
        $list   = array_filter($list);
        $list   = array_merge($list);
        if(strcasecmp($list[0], 'SELECT') !== 0)
            return array('code'=>0, 'message'=>"error, count sql is incorrect, first word must be SELECT: {$sql}");
        
        if(stripos($list[1], 'count(') !==0)
            return array('code'=>0, 'message'=>"error, count sql is incorrect, second word must be count(0), count(1) or count(*): {$sql}");

        $result = $this->query($sql);
        $count  = mysqli_fetch_assoc($result);
        $number = (int)array_values($count)[0];
        return array('code'=>1, 'message'=>'', 'data'=>$number);
    }
    
    
    /*
    * return array if exists data
    *   array('code'=>1, 'message'=>'', 'data'=>array())
    * return NULL if not exists data
    *   array('code'=>1, 'message'=>'', 'data'=>NULL)
    * die, if there is any error
    *   die json string or normal string
    */
    public function select($sql){        
        $query  = $this->query($sql);
        
        if($query->num_rows == 0) return array('code'=>1, 'message'=>'empty', 'data'=>NULL);
        
        $i      =0;
        $data   = array();
        while($model    = mysqli_fetch_assoc($query)){
            $data[$i]   = $model;
            $i++;
        }
        
        return array('code'=>1, 'message'=>'', 'data'=>$data);
    }



    /*
    * return array if exists data
    *   array('code'=>1, 'message'=>'', 'data'=>array())
    * return NULL if not exists data
    *   array('code'=>1, 'message'=>'', 'data'=>NULL)
    * die, if there is any error
    *   die json string or normal string
    */
    public function find($sql){
        $query  = $this->query($sql);
        $data   = mysqli_fetch_assoc($query);
        $result['code']         = 1;
        if(is_null($data) || !is_array($data)){
            $result['message']  = '';
            $result['data']     = NULL;
        }else{
            $result['message']  = '';
            $result['data']     = $data;
        }
        return $result;
    }
    
    
    
    public function arrayToInsertSql($array, $table, $hex=null, $ignore=false){
        if(!is_array($array))       die('error, first parameter in arrayToInsertSql() is not array');
        if(strlen($table) === 0)    die('error, second parameter in arrayToInsertSql() is not exist');
        
        $column_array   = array();
        $value_array    = array();
        foreach($array as $key=>$value){
            $column_array[] = "`{$key}`";
            if($hex === 'hex'){
                if(strlen($value) === 0) $value = '0x0';
                $value_array[] = $value;
            }else{
                if(is_null($value)){
                    $value_array[] = 'NULL';
                }else{
                    $value_array[] = "'".str_replace("'", "''", $value)."'";
                }  
            }
        }
        $columns        = implode(',', $column_array);
        $values         = implode(',', $value_array);
        
        if(strtolower($ignore) === 'ignore'){
            $sql        = "INSERT IGNORE INTO {$table} ({$columns}) VALUES({$values})";
        }else{
            $sql        = "INSERT INTO {$table} ({$columns}) VALUES({$values})";
        }
        
        return $sql;
    }
    
    
    /*
    * $config = array($table, $hex, $ignore);
    */
    public function multipleArrayToInsertSql($array, $config=array()){
        if(!is_array($config))
            die('{"code":0, "message":"error, config is not array, multipleArrayToInsertSql(), mysqli.class.php:241"}');
        
        $table  = @$config['table'];
        if(is_null($table))
            die('{"code":0, "message":"error, table does not exist in config parameter, multipleArrayToInsertSql(), mysqli.class.php:245"}');
        
        $hex    = @$config['hex'];
        if(!is_null($hex) && $hex!=='hex')
            die('{"code":0, "message":"error, hex muse be null or hex in config parameter, multipleArrayToInsertSql(), mysqli.class.php:251"}');
        
        $ignore = @$config['ignore'];
        if(!is_null($ignore) && $ignore!=='ignore')
            die('{code:0, "message":"error, ignore muse be null or ignore in config parameter, multipleArrayToInsertSql(), mysqli.class.php:255"}');
        
        $column_array   = array();
        foreach(current($array) as $key=>$value)
            $column_array[] = "`{$key}`";
        
        $value_list     = array();
        foreach($array as $value2){
            $value_list_element = array();
            foreach($value2 as $value3){
                if($hex === 'hex'){
                    if(strlen($value3) === 0)   $value3 = '0x0';
                    $value_list_element[]       = $value3;
                }else{
                    if(is_null($value3)){
                        $value_list_element[]   = 'NULL';
                    }else{
                        $value_list_element[]   = "'".str_replace("'", "''", $value3)."'";
                    }  
                }
            }
            $value_list[] = '(' . implode(',', $value_list_element) . ')';
        }
        
        $columns        = implode(',', $column_array);
        $values         = implode(',', $value_list);
        
        if(strtolower($ignore) === 'ignore'){
            $sql        = "INSERT IGNORE INTO {$table} ({$columns}) VALUES {$values}";
        }else{
            $sql        = "INSERT INTO {$table} ({$columns}) VALUES {$values}";
        }
        
        return $sql;
    }
    

    public function insert($sql){
        if(stripos($sql, 'insert') !== 0)   die("{\"code\":0, \"message\":\"insert sql is incorrect: {$sql}\"}");
        
        $host = $this->host;
        if($host === 'slave')
            return array('code'=>0, 'message'=>'insert must use master mysql, mysqli.class.php #235');
        
        if(is_null($this->connect_master)) $this->__connect();
        $connect    = $this->connect_master;

        $connect->query("SET NAMES 'utf8'");
        $connect->query($sql);
        
        $connect_error  = $connect->error;
        if(strlen($connect_error) > 0){
            $connect->close();
            $this->connect_master = NULL;
            die("{\"code\":0, \"message\":\"{$connect_error}, {$sql}, mysqli.class.php:304\"}");
        }
        
        $insert_id      = $connect->insert_id;
        $affected_rows  = $connect->affected_rows;
        
        $this->host = 'slave';
        
        return array('code'=>1, 'message'=>'', 'data'=>array('insert_id'=>$insert_id, 'affected_rows'=>$affected_rows));
    }

    
    public function arrayToUpdateSql($array, $table, $hex=null, $where_column_name){
        if(count($array) === 1)
            die('{"code":0, "message":"error, array count is only one, mysqli.class.php:263"}');
        
        if($hex !== 'hex')
            $array  = array_map(function($arr){return str_replace("'","''",$arr);},$array);
        
        if(is_null(@$array[$where_column_name]))
            die("{\"code\":0, \"message\":\"error, {$where_column_name} key does not exist in array, mysqli.class.php:325\"}");
        if(strlen($array[$where_column_name]) === 0)
            die("{$where_column_name} key's value is empty in array, mysqli.class.php:256");
        $where_column_value = $array[$where_column_name];
        if($hex === 'hex'){
            $where = "`{$where_column_name}`={$where_column_value}";
        }else{
            $where = "`{$where_column_name}`='{$where_column_value}'";
        }
        unset($array[$where_column_name]);
        
        $row    = array();
        foreach($array as $key=>$arr){
            if($hex === 'hex'){
                $row[] = "`{$key}`={$arr}";
            }else{
                $row[] = "`{$key}`='{$arr}'";
            }
        }

        $sql    = "update `{$table}` set " . implode(', ',$row) . " where {$where}";
        return $sql;
    }
    
    
    /*
    * return array('code'=>1, 'message'=>'', 'data'=>$affected_rows);
    * $affected_rows, 0, 1 or more intger
    * $affected_rows=0, update failed, or old data and same data are the same so that data has not been changed.
    * die, if there is any error.
    */
    public function update($sql){
        if(stripos($sql, 'update') !== 0)
            die("{\"code\":0, \"message\":\"update sql is incorrect: {$sql}, mysqli.class.php:253\"}");
        
        $host = $this->host;
        if($host === 'slave')
            die("{\"code\":0, \"message\":\"update must use master mysql, mysqli.class.php #306\"}");
        
        if(is_null($this->connect_master)) $this->__connect();
        $connect        = $this->connect_master;

        $connect->query("SET NAMES 'utf8'");
        $connect->query($sql);
        
        $connect_error  = $connect->error;
        if(strlen($connect_error) > 0){
            $connect->close();
            $this->connect_master = NULL;
            die("{\"code\":0, \"message\":\"{$connect_error}, mysqli.class.php:377\"}");
        }
        
        $affected_rows  = $connect->affected_rows;
        
        $this->host     = 'slave';
        
        return array('code'=>1, 'message'=>'', 'data'=>$affected_rows);
    }
    
    
    public function delete($sql){
        if(stripos($sql, 'delete') !== 0)   die("{\"code\":0, \"message\":\"delete sql is incorrect: {$sql}, mysqli.class.php:386\"}");
        
        $host = $this->host;
        if($host === 'slave')
            die("{\"code\":0, \"message\":\"delete must use master mysql, mysqli.class.php #389\"}");
        
        if(is_null($this->connect_master)) $this->__connect();
        $connect        = $this->connect_master;
        
        $connect->query("SET NAMES 'utf8'");
        $connect->query($sql);
        
        $connect_error  = $connect->error;
        if(strlen($connect_error) > 0){
            $connect->close();
            $this->connect_master = NULL;
            die("{$connect_error}, {$sql}, mysqli.class.php:404");
        }
        
        $this->host     = 'slave';
        
        return array('code'=>1, 'message'=>'');
    }
    

    /*
    * $type, string, int
    */
    public function string2hex($data, $type=null){
        if(is_null($data)) return NULL;
        
        if(strlen($data) === 0)   
            die('{"code":0, "message":"error, data is empty in string2hex() method, mysqli.class.php #420"}');
        
        if(is_null($type))
            die('{"code":0, "message":"error, string2hex parameter type must exist, mysqli.class.php #423"}');
        if(!in_array($type, array('string', 'int')))
            die('{"code":0, "message":"error, string2hex parameter type only support string and int, mysqli.class.php #345"}');
        
        if($type === 'int'){
            if(!is_numeric($data))
                die("{\"code\":0, \"message\":\"error, data is not number, {$data}, mysqli.class.php #429\"}");
            if($data >= 0){
                $hex = '0x'.dechex($data);
            }else{
                $hex = '-0x' . dechex(0-$data);
            }
            return $hex;
        }
        
        $data   = (string)$data;
        $hex    = '';
        for($i=0;$i<strlen($data);$i++){
            $decimal    = ord($data[$i]);
            if($decimal > 15){
                $hex    .= dechex($decimal);
            }else{
                $hex    .= '0' . dechex($decimal);
            }
            unset($decimal);
        }
        return '0x'.$hex;
    }

    
    /*
    * $type, 
    */
    public function value2hex($data, $type=null){
        if(is_null($data)) return NULL;
        
        if(is_null($type)){
            $type = gettype($data);
            
        }
        var_dump(gettype($data));
        
        /*
        if(strlen($data) === 0)   
            die('{"code":0, "message":"error, data is empty in value2hex() method, mysqli.class.php #432"}');
        
        if(is_null($type))
            die('{"code":0, "message":"error, string2hex parameter type must exist, mysqli.class.php #343"}');
        if(!in_array($type, array('string', 'int')))
            die('{"code":0, "message":"error, string2hex parameter type only support string and int, mysqli.class.php #345"}');
        
        if($type === 'int'){
            if(!is_numeric($data))
                die('{"code":0, "message":"error, data is not number, mysqli.class.php #405"}');
            if($data >= 0){
                $hex = '0x'.dechex($data);
            }else{
                $hex = '-0x' . dechex(0-$data);
            }
            return $hex;
        }
        
        $hex = '';
        for($i=0;$i<strlen($data);$i++){
            $decimal    = ord($data[$i]);
            if($decimal > 15){
                $hex    .= dechex($decimal);
            }else{
                $hex    .= '0' . dechex($decimal);
            }
            unset($decimal);
        }
        return '0x'.$hex;
        */
    }
    
    
    public function __destruct(){
        if($this->close){
            $connect_master = $this->connect_master;
            $connect_slave  = $this->connect_slave;
            if(!is_null($connect_master) && is_object($connect_master)){
                $connect_master->close();
                $this->connect_master = NULL;
            }
            if(!is_null($connect_slave) && is_object($connect_slave)){
                $connect_slave->close();
                $this->connect_slave = NULL;
            }
        }
    }
    
    
}