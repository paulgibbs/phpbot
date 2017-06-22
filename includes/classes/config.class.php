<?php
/********************************************************
* config.class.php By DJPaul (pgibbs@gmail.com)
*
* In regards to DMBot Bug #0000161
* 
* This Code was written with the assistance of
* The Place of Dangerous Minds
* http://WWW.Dangerous-Minds.NET who has supported me
* in many of my projects. Please take the time to visit
* them and support them as well.
*
* Known Issues:
* May not function properly under DOS
*
*
* this is a modified version for the IRC Bot project
* this one cannot be re distributed
*
* Created on 02/21/06 By DJPaul
* Last Edited on 
*
********************************************************/


class Config {
	protected static $bHasConfigBeenLoaded=false;
	protected static $configData=array();


	/**
	  * @param string $cfgFile
  	* @desc Loads the configuration variables into self::$configData.
	  */
	static public function LoadConfigValues($cfgFile) {
		if (file_exists($cfgFile)) {
			self::$configData = file($cfgFile);
		} else {
			trigger_error("Configuration File: {$cfgFile} Does not exist.", E_USER_ERROR);
			exit;
		}
	}

	/**
  	* @return string
	  * @param string $var, bool $array, $module
  	* @desc Gets $var's value from the configuration variables.
	  */
	static public function GetValue($var, $array=0, $module='') {
		if ($module != '') {
			$var = $module."_".$var;
		}
		$val = NULL;

		foreach (self::$configData as $line_num => $line) {
			$line = str_replace("\r", "", $line);

			/* Added By Wammy@dangerous-minds.net
			* Approved by Wammy@dangerous-minds.net on 02/16/06
			* In regards to DMBot Bug #0000129
			*/
			$cn = strlen($line);
			if ($line[$cn-1] != "\n") {
				$line .= "\n";
			}

			if (substr($line, 0, 1) != "" || substr($line, 0, 1) != "#") {
				$VariableName = substr($line, 0, strpos($line, "=")); // Parse out Variable Name
				$VariableValue = substr($line, strpos($line, "=")+1,strpos($line, "\n")-(strpos($line, "=") + 1)); //Parse Out Variable Value
				if ($VariableName == $var) {

					if ($array == 1) {
						$val = explode(",", $VariableValue);
					} else {
						$val = $VariableValue;
					}
					break;
				}
			}
		}

		reset(self::$configData);
		unset($lines);
		unset($line_num);
		unset($line);
		return $val;
	}
}
?>