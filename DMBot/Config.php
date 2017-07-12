<?php
namespace DMBot;
use Dotenv\Dotenv;

/**
 * Config loader
 * 
 * @author wammy21@gmail.com
 */
class Config {

    /**
     * DotEnv Container.
     * @var Dotenv\Dotenv  
     */
    private $_dotEnv;
    
    /**
     *  Current settings file.
     * @var string Loaded file.  
     */
    public $file;
    public function __construct($dir, $file = '.env') {
        $this->file = $file;
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
