<?php
namespace brick;
class mo{

    public $language = null;
    public $domain   = null;
    
    public function main($string){
        $language = $this->language;
        
        putenv('LANG={$language}');
        if(!setlocale(LC_ALL, "{$language}.UTF-8")){
            echo $string;
            die('error, setlocale() failed, mo.class.php:15');
        }
        
        $domain   = ($this->domain).'-'.$language;
        $directory = APP_PATH.'locale';
        bindtextdomain($domain, $directory);
        textdomain($domain);
        echo gettext($string);
    }
    
    
    public function log($message){
        $message = date('Y-m-d H:i:s') . "\r\n{$message}\r\n\r\n";
        echo $message;
    }
    

}
