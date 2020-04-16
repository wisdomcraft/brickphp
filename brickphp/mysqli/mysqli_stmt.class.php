<?php
namespace brick;
class mysqli_stmt{


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
			die("{\"status\":\"error\", \"message\":\"{$connect_error}, mysqli.class.php #90\"}");
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
            die($connect_error);
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
            return array('status'=>'error', 'message'=>"error, count sql is incorrect, first word must be SELECT: {$sql}");
        
        if(stripos($list[1], 'count(') !==0)
            return array('status'=>'error', 'message'=>"error, count sql is uncorrect, second word must be count(0), count(1) or count(*): {$sql}");

        $result = $this->query($sql);
        $count  = mysqli_fetch_assoc($result);
        $number = (int)array_values($count)[0];
        return array('status'=>'success', 'message'=>'', 'data'=>$number);
    }
    
    
    /*
    * return array if exists data
    *   array('status'=>'success', 'message'=>'', 'data'=>array())
    * return NULL if not exists data
    *   array('status'=>'success', 'message'=>'', 'data'=>NULL)
    * die, if there is any error
    *   die json string or normal string
    */
    public function select($sql, $variable=array()){
        if(stripos($sql, 'select') !== 0)   die("{\"status\":\"error\", \"message\":\"select sql is incorrect: {$sql}\"}");
        $host           = $this->host;
        if($host === 'master'){
            if(is_null($this->connect_master))  $this->__connect();
            $connect    = $this->connect_master;
        }elseif($host === 'slave'){
            if(is_null($this->connect_slave))   $this->__connect();
            $connect    = $this->connect_slave;
        }
        
        $connect->set_charset('utf8');
        
        if($stmt = $connect->prepare($sql)){
            $stmt->execute();
            $result     = $stmt->get_result();
            $stmt->close();
        }else{
            $error      = $connect->error;
            $connect->close();
            unset($connect);
            if($host === 'master')  $this->connect_master = NULL;
            if($host === 'slave')   $this->connect_slave  = NULL;
            die("{\"code\":0, \"message\":\"stmt failed, sql: {$sql}, {$error}, mysqli_stmt.class.php #175\"}");
        }
        
        $this->host     = 'slave';
        if($result->num_rows == 0) return array('code'=>1, 'message'=>'empty', 'data'=>NULL);
    
        $i      =0;
        $data   = array();
        while($model    = mysqli_fetch_assoc($result)){
            $data[$i]   = $model;
            $i++;
        }
        return array('code'=>1, 'message'=>'', 'data'=>$data);
    }



    /*
    * return array if exists data
    *   array('status'=>'success', 'message'=>'', 'data'=>array())
    * return NULL if not exists data
    *   array('status'=>'success', 'message'=>'', 'data'=>NULL)
    * die, if there is any error
    *   die json string or normal string
    */
    public function find($sql){
        $query  = $this->query($sql);
        $data   = mysqli_fetch_assoc($query);
        $result['status']       = 'success';
        if(is_null($data) || !is_array($data)){
            $result['message']  = '';
            $result['data']     = NULL;
        }else{
            $result['message']  = '';
            $result['data']     = $data;
        }
        return $result;
    }
    
    
    //$table, $hex=null, $ignore=false
    public function arrayToInsertSql($array, $variable=array()){
        if(!is_array($array))                   die("{\"status\":\"error\", \"message\":\"first parameter in arrayToInsertSql() is not array\"}");
        if(strlen(@$variable['table']) === 0)   die("{\"status\":\"error\", \"message\":\"table name not exists in arrayToInsertSql()\"}");
        
        $table          = $variable['table'];
        $ignore         = @$variable['ignore'];
        
        $column_array   = array();
        $value_array    = array();
        foreach($array as $key=>$value){
            $column_array[] = "`{$key}`";
            $value_array[]  = "?";
        }
        $columns        = implode(',', $column_array);
        $values         = implode(',', $value_array);
        
        if(@strtolower($ignore) === 'ignore'){
            $sql        = "INSERT IGNORE INTO {$table} ({$columns}) VALUES({$values})";
        }else{
            $sql        = "INSERT INTO {$table} ({$columns}) VALUES({$values})";
        }
        
        return $sql;
    }
    
    
    /*
    * $config = array($table, $hex, $ignore);
    */
    public function multipleArrayToInsertSql($array, $config){
        $table  = @$config['table'];
        if(is_null($table))
            die('{"code":0, "message":"error, table does not exist in config parameter, multipleArrayToInsertSql(), mysqli.class.php:236"}');
        
        $hex    = @$config['hex'];
        if(!is_null($hex) && $hex!=='hex')
            die('{"code":0, "message":"error, hex muse be null or hex in config parameter, multipleArrayToInsertSql(), mysqli.class.php:240"}');
        
        $ignore = @$config['ignore'];
        if(!is_null($ignore) && $ignore!=='ignore')
            die('{"code":0, "message":"error, ignore muse be null or ignore in config parameter, multipleArrayToInsertSql(), mysqli.class.php:240"}');
        
        $column_array   = array();
        foreach($array[0] as $key=>$value)
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
    

    public function insert($sql, $data){
        if(stripos($sql, 'insert') !== 0)   die("{\"status\":\"error\", \"message\":\"insert sql is incorrect: {$sql}\"}");
        
        $host = $this->host;
        if($host === 'slave')
            return array('status'=>'error', 'message'=>'insert must use master mysql, mysqli_stmt.class.php #294');
        
        if(is_null($this->connect_master)) $this->__connect();
        $connect    = $this->connect_master;

        $connect->set_charset('utf8');
        if($stmt = $connect->prepare($sql)){
            //$stmt->execute();
            //$result     = $stmt->get_result();
            //$stmt->close();
        }else{
            $connect->close();
            $this->connect_master = NULL;
            die("{\"status\":\"success\", \"message\":\"stmt failed, sql: {$sql}, mysqli_stmt.class.php #307\"}");
        }
        
        /*
        $insert_id      = $connect->insert_id;
        $affected_rows  = $connect->affected_rows;
        
        $this->host = 'slave';
        
        return array('status'=>'success', 'message'=>'', 'data'=>array('insert_id'=>$insert_id, 'affected_rows'=>$affected_rows));
        */
    }

    
    public function arrayToUpdateSql($array, $table, $hex=null, $where_column_name){
        $array  = array_filter($array);
        if(count($array) === 1)
            die('{"status":"error", "message":"error, array count is only one, mysqli.class.php:263"}');
        
        if($hex !== 'hex')
            $array  = array_map(function($arr){return str_replace("'","''",$arr);},$array);
        
        if(is_null(@$array[$where_column_name]))
            die("{$where_column_name} key does not exist in array, mysqli.class.php:254");
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
    * return array('status'=>'success', 'message'=>'', 'data'=>$affected_rows);
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
            die($connect_error . 'mysqli.class.php:318');
        }
        
        $affected_rows  = $connect->affected_rows;
        
        $this->host     = 'slave';
        
        return array('status'=>'success', 'message'=>'', 'data'=>$affected_rows);
    }
    
    
    public function delete($sql){
        if(stripos($sql, 'delete') !== 0)   die("{\"status\":\"error\", \"message\":\"delete sql is incorrect: {$sql}, mysqli.class.php:318\"}");
        $result = $this->query($sql);
        return $result;
    }
    

    public function string2hex($data, $type=null){
        if(is_null($data)) return NULL;
        
        if(strlen($data) === 0)   
            die('{"status":"error", "message":"error, data is empty in string2hex() method, mysqli.class.php #340"}');
        
        if(is_null($type))
            die('{"status":"error", "message":"error, string2hex parameter type must exist, mysqli.class.php #343"}');
        if(!in_array($type, array('string', 'int')))
            die('{"status":"error", "message":"error, string2hex parameter type only support string and int, mysqli.class.php #345"}');
        
        if($type === 'int'){
            if(!is_numeric($data))
                die('{"status":"error", "message":"error, data is not number, mysqli.class.php #405"}');
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
    }

    
    public function __destruct(){
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