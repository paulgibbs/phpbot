<?php

namespace DMBot;

/**
 * Theme tokeniser.
 * Please note that the colour formatting codes in $formatting_tokens are from mIRC and may not all work in other clients.
 * 
 * 
 * 1) Loads modules/{module name}/template.txt which is formatted like so:
 * line 1: success_message=Everything's almost as good as @@replace_this@@!
 * line 2: error_message=Uh oh, someone did a @@bad_thing@@ :(
 * 
 * 
 * 2) Applies tokens and renders output message.
 * 
 * 
 * To use this in your module, do as follows:
 * $tokens = array('replace_this' => 'chocolate icecream');
 * Tokeniser::tokenise('module name', 'success_message', $tokens);
 * 
 * Would output:
 * "Everything's almost as good as chocolate icecream!"
 * 
 * @version 1.1
 * @author djpaul@gmail.com
 * @author wammy21@gmail.com
 */
class Tokeniser {

    public static $template = array();
    public static $formatting_tokens = array();
    public static $bLoadedFormattingTokens = false;

    /**
     * Load module template file
     * 
     * @param string $modulename
     */
    public static function loadTemplates($modulename) {
        $path = DMBOT_BASE . '/modules/' . $modulename . '/template.txt';
        if(file_exists($path)) {
            $contents = file($path);
        }

        /* e.g. $contents[0] == "key=value" */
        for ($i = 0; $i < count($contents); $i++) {
            $seperator = strpos($contents[$i], '=');
            $key = substr($contents[$i], 0, $seperator);
            $value = substr($contents[$i], $seperator + 1, strlen($contents[$i]) - $seperator);

            self::$template[$modulename][$key] = $value;
            unset($key, $value, $seperator);
        }
    }

    /**
     * Applies tokens.
     * Returns formatted text.
     * 
     * @param string $modulename name of module
     * @param string $keyname name of line of template to process
     * @param array $tokens associative array of tokens/values
     * @return string
     */
    public static function tokenise($modulename, $keyname, $tokens = array()) {
        /* is relevant template loaded? */
        if (!isset(self::$template[$modulename])) {
            self::loadTemplates($modulename);
        }

        /* are the formatting tokens loaded? */
        if (!self::$bLoadedFormattingTokens) {
            self::$formatting_tokens = array(
                'white' => chr(3) . "00",
                'black' => chr(3) . "01",
                'blue' => chr(3) . "02",
                'green' => chr(3) . "03",
                'red' => chr(3) . "04",
                'brown' => chr(3) . "05",
                'purple' => chr(3) . "06",
                'orange' => chr(3) . "07",
                'yellow' => chr(3) . "08",
                'light_green' => chr(3) . "09",
                'teal' => chr(3) . "10",
                'aqua' => chr(3) . "11",
                'light_blue' => chr(3) . "12",
                'pink' => chr(3) . "13",
                'grey' => chr(3) . "14",
                'light_grey' => chr(3) . "15",
                'bold' => chr(2),
                'underline' => chr(31),
                'reverse' => chr(18),
                'clear' => chr(15)
            );
            self::$bLoadedFormattingTokens = true;
        }

        /* invalid key? */
        if (!isset(self::$template[$modulename][$keyname])) {
            return 'INVALID_KEY';
        }

        /* apply tokens */
        $output = self::$template[$modulename][$keyname];

        if (!empty($tokens)) {
            while ($value = current($tokens)) {
                $output = str_replace('@@' . key($tokens) . '@@', $value, $output);
                next($tokens);
            }
        }

        /* apply formatting tokens */
        while ($value = current(self::$formatting_tokens)) {
            $output = str_replace('@@' . key(self::$formatting_tokens) . '@@', $value, $output);
            next(self::$formatting_tokens);
        }
        reset(self::$formatting_tokens);

        return $output;
    }

}
