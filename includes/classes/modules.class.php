<?php
/********************************************************
* modules.class.php By Wammy (wammy21@gmail.com)
*
* The core of the DM Bot
* 
* This Code was written with the assistance of
* The Place of Dangerous Minds
* http://WWW.Dangerous-Minds.NET who has supported me
* in many of my projects. Please take the time to visit
* them and support them as well.
*
* Known Issues:
* 
*
*
*
* Created on 00/00/05 By Wammy
* Last Edited on 
*
********************************************************/


Class Modules {
	public $ar_Modules=array();
	private $loadmods=array();
	function __construct() {
		global $DMBot,$Modules;
		$DMBot->Debug(1,BLUE."Loading Modules".NORMAL);
		$Modules = $this;
		$dir = $DMBot->ar_botcfg['modulesdir'];
		$len = strlen($dir);
		if($dir[$len-1] != "/") {
			$DMBot->Debug(1,BLUE."Adding trailing slash.".NORMAL);
			$dir .="/";
		}
		$loadmods = array();
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(filetype($dir . $file) == 'dir') {
						if ($file != '.' && $file != '..' && $file != '.svn') {
							array_push($loadmods,$file);
						}
					}
				}
				closedir($dh);
			}
		}

		/* changed by Wammy@dangerous-minds.net
		* 02/16/06
		* In regards to DMBot Bug #0000125
		*/
		for($y=0;$y<=6;$y++) {
			$DMBot->Debug(1,"Loading Modules ($y)");
			for($i = 0; $i < count($loadmods); $i++) {
				$loadmods[$i] = str_replace(".php","",$loadmods[$i]);
				$loadmods[$i] = str_replace($dir,"",$loadmods[$i]);
				if(file_exists("$dir/{$loadmods[$i]}/{$loadmods[$i]}.php")) {
					$upper = strtoupper($loadmods[$i]);
					if(file_exists("$dir/{$loadmods[$i]}/level_$y")) {
						/*
						* Changed by wammy@dangerous-minds.net
						* 02/16/06
						* In regards to DMBot Bug #0000158 
						*/
						if(!isset($Modules->ar_Modules[$upper])) {
							if(!class_exists($upper)) {
								include "$dir/{$loadmods[$i]}/{$loadmods[$i]}.php";
							}
							$$loadmods[$i] = new $upper();
							$Modules->add_obj($upper,$$loadmods[$i]);
							$this->run('ONLOAD',$upper);
						}
					} elseif($y == 6) {
						if(!isset($Modules->ar_Modules[$upper])) {
							if(!class_exists($upper)) {
								include "$dir/{$loadmods[$i]}/{$loadmods[$i]}.php";
							}
							$$loadmods[$i] = new $upper();
							$Modules->add_obj($upper,$$loadmods[$i]);
							$this->run('ONLOAD',$upper);
						}
					}


				}
			}
		}




	}

	public function add_module($shrt,$desc,$name,$version,$url) {
		global $Modules,$DMBot;
		$DMBot->Debug(1,BLUE."Adding Module ($shrt)".NORMAL);
		$Modules->ar_Modules[$shrt]['name'] = $name;
		$Modules->ar_Modules[$shrt]['desc'] = $desc;
		$Modules->ar_Modules[$shrt]['version'] = $version;
		$Modules->ar_Modules[$shrt]['url'] = $url;
		$Modules->ar_Modules[$shrt]['obj'] = null;
	}
	public function run($event,$module=null) {
		global $DMBot,$Modules;
		$ar_mods = $Modules->ar_Modules;

		/* changed by Wammy@dangerous-minds.net
		* 02/16/06
		* In regards to DMBot Bug #0000125
		*/
		if($module==null) {
			while (list ($key, $val) = each ($ar_mods)) {
				if(method_exists($Modules->ar_Modules[$key]['obj'],$event)) {
					$Modules->ar_Modules[$key]['obj']->$event($DMBot->ar_message);
				}
			}
		} else {
			if(method_exists($module,$event)) {
				$Modules->ar_Modules[$module]['obj']->$event($DMBot->ar_message);
			}
		}

	}
	public function add_obj($shrt,$obj) {
		global $Modules;

		$Modules->ar_Modules[$shrt]['obj'] = $obj;

	}
}

