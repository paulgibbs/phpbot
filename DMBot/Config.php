<?php
namespace DMBot;
use Dotenv\Dotenv;

/**
 * Config loader
 * 
 * @author wammy21@gmail.com
 */
class Config {

    private $_dotEnv;
    public function __construct($dir, $file = '.env') {
        $this->_dotEnv = new Dotenv($dir, $file);
        $this->_dotEnv->load();
    }
    
    public function __get($name) {
        return getenv($name);
    }
    
    static function get($name) {
        return getenv($name);
    }

}
