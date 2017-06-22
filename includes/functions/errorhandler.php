<?php
/********************************************************
* ErrorHandler.php By Wammy (wammy21@gmail.com)
*
* This Code was written with the assistance of
* The Place of Dangerous Minds
* http://WWW.Dangerous-Minds.NET who has supported me
* in many of my projects. Please take the time to visit
* them and support them as well.
*
* Known Issues:
* 
* Apperantly PHP doesnt like to handle Core or Compile
* Errors with a differnt error handler so fatal errors
* are not caught by this script/function
*
*
* this is a modified version for the IRC Bot project
* this one cannot be re distributed
*
*
********************************************************/

function ErrorHandler($errno, $errstr, $errfile, $errline)
{
	global $DMBot;
	$ShowBackTrace = 1; //Show PHP Backtrace
	$ShowFullFilePath = 0; //Show (or not) the full file path on errors.
	
	if($ShowFullFilePath == 0)
	{
		$file = explode("/",$errfile); //we dont really need the whole path, do we?
		$num = count($file);
	}
	elseif($ShowFullFilePath == 1)
	{
		$file['0'] = $errfile;
		$num = count($file);
	}
        $errtype = 'Unkown';
	switch ($errno) //Error types
	{
		case 1:
		$errtype = "Error";
		break;
		case 2:
		$errtype = "Warning";
		break;
		case 4:
		$errtype = "Parse Error";
		break;
		case 8:
		$errtype = "Notice";
		break;
		case 16:
		$errtype = "Core Error";
		$exit=1;
		break;
		case 32:
		$errtype = "Core Warning";
		break;
		case 64:
		$errtype = "Compile Error";
		$exit=1;
		break;
		case 128:
		$errtype = "Compile Warning";
		break;
		case 256:
		$errtype = "User Error";
		break;
		case 512:
		$errtype = "User Warning";
		break;
		case 1024:
		$errtype = "User Notice";
		break;
		case 2047:
		$errtype = "Global Error";
		break;
		case 2048:
		$errtype = "Strict Error";
		break;
	}
	$msg = "Error Information ($errtype): Error NO: $errno On Line: $errline In File: ".$file[$num-1]." Error: $errstr";
	
	if(is_object($DMBot)) {
		if($DMBot->int_registered){
			$DMBot->PrivMsg($msg,$DMBot->ar_botcfg['owner']);
		}
		else {
			echo $msg."\n";
		}
		
	}
	else {
		echo $msg."\n";
	}


}

