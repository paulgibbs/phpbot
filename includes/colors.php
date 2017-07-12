<?php
/********************************************************
* colors.php By I_Am
*
* Allows Calling for colors in the shell(unix).
* Support on other prompts has not been tested ex. DOS.
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
* Created on 8/11/06 By I_Am
* Last Edited on 8/11/06 By I_Am
*
********************************************************/

define("NORMAL",Chr(27)."[0m");

define("BOLD",Chr(27)."[1m");

define("UNDERLINE",Chr(27)."4m");

define("BLACK", Chr(27)."[30m");
define("BLACKBG", Chr(27)."[40m");

define("RED", Chr(27)."[31m");
define("REDBG", Chr(27)."[41m");

define("GREEN", Chr(27)."[32m");
define("GREENBG", Chr(27)."[42m");

define("YELLOW", Chr(27)."[33m");
define("YELLOWBG", Chr(27)."[43m");

define("BLUE", Chr(27)."[34m");
define("BLUEBG", Chr(27)."[44m");

define("PURPLE", Chr(27)."[35m");
define("PURPLEBG", Chr(27)."[45m");

define("CYAN", Chr(27)."[36m");
define("CYANBG", Chr(27)."[46m");

define("WHITE", Chr(27)."[37m");
define("WHITEBG", Chr(27)."[47m");