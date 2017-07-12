<?php
namespace DMBot;

use DMBot\IRC\Message;

/** Module handler
 * 
 * @author wammy21@gmail.com
 */
class Modules {

    /**
     *
     * @var array Loaded Modules
     */
    private $_Modules = [];

    /**
     * 
     * @global DMBot\Bot $bot 
     * @param type $modules Array of modules to load
     */
    public function loadModules($modules) {
        global $bot;

        if (!empty($modules)) {
            for ($loadLevel = 0; $loadLevel <= 6; $loadLevel++) {
                $bot->Debug(1, "Loading Modules ($loadLevel)");

                foreach ($modules as $module) {
                    if (file_exists(DMBOT_BASE . '/modules/' . $module . '/level_' . $loadLevel)) {
                        $this->loadModule($module);
                    } else if ($loadLevel == 6) {
                        $this->loadModule($module);
                    }
                }
            }
        }
    }

    /**
     * Load the specific module
     * @param string $module Module to load.
     */
    public function loadModule($module) {
        if (!empty($module) && !isset($this->_Modules[$module])) {
            $path = DMBOT_BASE . '/modules/' . $module . '/' . $module . '.php';
            if (file_exists($path)) {
                include $path;
            } else {
                trigger_error('Unable to load module: ' . $module, E_USER_NOTICE);
            }
        }
    }

    /**
     * Add/Initiate the specified module
     * 
     * @global DMBot\Bot $bot
     * @param string $slug Module 
     * @param string $class Module class to initiate.
     */
    public function add($slug, $class) {
        global $bot;
        $bot->Debug(1, BLUE . "Adding Module ($slug)" . NORMAL);
        $instance = new $class();

        $this->_Modules[$slug] = $instance;

        $this->run('ONLOAD', null, $slug);
    }

    /**
     * Static version of ->add
     * 
     * @global DMBot\Bot $bot
     * @param string $slug Module 
     * @param string $class Module class to initiate.
     */
    static function addModule($slug, $class) {
        global $bot;
        $bot->modules->add($slug, $class);
    }

    /**
     * Run the specific event. 
     * 
     * @param string $event
     * @param Message $Message
     * @param string $module
     */
    public function run($event, Message $Message = null, $module = null) {
        if ($module == null) {
            foreach ($this->_Modules as $Module) {
                if (method_exists($Module, $event)) {
                    $Module->$event($Message);
                }
            }
        } else if (array_key_exists($module, $this->_Modules)) {
            if (method_exists($this->_Modules[$module], $event)) {
                $this->_Modules[$module]->$event($Message);
            }
        }
    }

}
