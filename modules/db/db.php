<?php
/* Version 1.4.
   Last updated 16/2/6 22:11 GMT by DJPaul.

   Generic MySQL DB class.
   From a DMBot perspective, this does nothing on itself.  To use it in your components, do:

   0) Make sure your class doesn't get loaded before this one (see bug #0000125)

   1) Put this code in your class' constructor:
	 function __construct() {
		 global $Modules;
     $this->_db = &$Modules->ar_Modules['DB']['obj']; 
   }

   2) Query the DB via (you may want to write a wrapper query() function to simplify your code.):
   $this->_db->query($sql);
*/
class DB {
	protected $_db;

	function __destruct() {
		if ($this->_db) {
			mysql_close($this->_db);
		}
	}
	
	function __construct() {
		global $Modules, $DMBot;

		$Modules->add_module('DB', 'DB component', 'DB component', '1.5', '');
		$this->connect($DMBot);
	}
	
	/* connect to DB */
	protected function connect(&$DMBot) {
		$server = Config::GetValue('server', 0, 'db');
		$username = Config::GetValue('user', 0, 'db');
		$password = Config::GetValue('pass', 0, 'db');
		$dbname = Config::GetValue('dbname', 0, 'db');
 		
		$this->_db = mysql_connect($server, $username, $password, true) or die("[DB::connect 1] " . mysql_error());
		mysql_select_db($dbname, $this->_db) or die("[DB::connect 2] " . mysql_error());
	}
	
	/* query the DB */
	public function query($sql) {
		$bSkip = false;
		$result = mysql_query($sql, $this->_db) or die("[DB::query 1] " . mysql_error());
		$s = "SELECT";
		
		$is_select = (substr($sql, 0, strlen($s)) == $s);
		if ($is_select) {
  		/* no results returned */
		  if (!mysql_num_rows($result)) {
	  	  return false;	
  		} else {
	  	  while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		      if (!$bSkip && !isset($output)) {
			      $output = array($row);
			      $bSkip = true;
		      } else {
		    	  $output[] = $row;
	        }
	    	}

    		return $output;
      }
	  } else {
		  return $result;
	  }
	}
	
	/* returns the mysql_insert_id() of the last relevant query */
	public function get_mysql_insert_id() {
		return mysql_insert_id($this->_db);
	}
}