<?php
namespace brick;
class postgre{

    
    public $config = array();
    
    
    public function __construct(){
        if(!extension_loaded('pgsql')) die("{\"status\":\"error\", \"description\":\"your server need php extension pgsql!\"}");
        
        if(!file_exists(APP_PATH.'config/config.php'))
            die("{\"status\":\"error\", \"description\":\"config file is not exist in application! postgresql.class.php #13\"}");
        $db = require(APP_PATH.'config/config.php');

        $psql_config['host'] = $db['PSQL_HOST'];
        $psql_config['name'] = $db['PSQL_NAME'];
        $psql_config['user'] = $db['PSQL_USER'];
        $psql_config['pwd']  = $db['PSQL_PWD'];
        $psql_config['port'] = $db['PSQL_PORT'];
        $this->config = $psql_config;
    }
    
    
    public function __connect(){
        $config = $this->config;
        $conn   = @pg_connect("host={$config['host']} port={$config['port']} dbname={$config['name']} user={$config['user']} password={$config['pwd']}");
        if(!$conn) die("{\"status\":\"error\", \"description\":\"connect postgresql server failed!\"}");
        return  $conn;
    }
    
    
    public function find($sql){
        $conn   = $this->__connect();
        @$query = pg_query($conn, $sql);
        if(!$query){
            $result = array('status'=>'error', 'description'=>"query failed about sql: {$sql}", 'error'=>pg_last_error());
            die(json_encode($result));
        }
        
        $data   = pg_fetch_assoc($query);
        if(!$data){
            $result = array('status'=>'success', 'data'=>NULL);
        }else{
            $result = array('status'=>'success', 'data'=>$data);
        }
        return json_encode($result);
    }
    
    
    public function select($sql){
        $conn   = $this->__connect();
        @$query = pg_query($conn, $sql);
        if(!$query){
            $result = array('status'=>'error', 'description'=>"query failed about sql: {$sql}", 'error'=>pg_last_error());
            die(json_encode($result));
        }
        
        $i=0;
        while($model    = pg_fetch_assoc($query)){
            $list[$i]   = $model;
            $i++;
        }
        
        $result = @array('status'=>'success', 'data'=>$list);
        return json_encode($result);
    }
    
    
    public function count($sql){
        $conn   = $this->__connect();
        $result = @pg_query($conn, $sql);
        $count  = @pg_fetch_assoc($result);
        if(!$count || is_null($count))
            die("{\"status\":\"error\", \"description\":\"get count number failed from database! {$sql}\"}");
        
        return "{\"status\":\"success\", \"data\":\"{$count['count']}\"}";
    }
    
    
    public function table_exists_check($sql){
        return $this->count($sql);
    }
    
    
    public function arrayToInsertSql($table, $array){
        $column_array   = array();
        $value_array    = array();
        foreach($array as $key=>$str){
            $column_array[] = "\"{$key}\"";
            if(strlen($str)==0){
                $value_array[]  = "NULL";
            }else{
                $value_array[]  = "'" . str_replace("'", "''", $str) . "'";
            }
        }
        $columns        = implode(',', $column_array);
        $values         = implode(',', $value_array);
        
        $sql            = "INSERT INTO {$table} ({$columns}) VALUES({$values})";
        return $sql;
    }
    
    
    public function add($sql){
        $conn   = $this->__connect();
        @$query = pg_query($conn, $sql);
        if(!$query || !pg_result_status($query)){
            $result = array('status'=>'error','description'=>pg_last_error());
            die(json_encode($result));
        }
           
        return "{\"status\":\"success\"}";
    }
    
    
    public function arrayToUpdateSql($table, $array, $where){
        $data   = array();
        foreach($array as $key=>$str){
            if(strlen($str)==0){
                $data[] = "\"{$key}\"=NULL";
            }else{
                $value  = str_replace("'", "''", $str);
                $data[] = "\"{$key}\"='{$value}'";
                unset($value);
            }
        }        
        $set    = implode(',', $data);
        
        $sql    = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        return $sql;
    }
    
    
    public function save($sql){
        $conn   = $this->__connect();
        @$query = pg_query($conn, $sql);
        
        if(!$query || !pg_result_status($query)){
            $result = array('status'=>'error','description'=>pg_last_error());
            die(json_encode($result));
        }
        
        return "{\"status\":\"success\"}";
    }
    
    
    public function delete($sql){
        $conn   = $this->__connect();
        @$query = pg_query($conn, $sql);
        
        if(!$query){
            $result = array('status'=>'error','description'=>pg_last_error());
            die(json_encode($result));
        }
        
        return "{\"status\":\"success\"}";
    }


}
