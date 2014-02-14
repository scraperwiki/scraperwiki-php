<?php

require 'rb.php';

// This automatically instantiates the class with default connection settings, but requires you to manually instantiate if running locally
if (empty($_SERVER["SERVER_ADDR"]) OR stripos($_SERVER["SERVER_ADDR"], '127.0.0.1') === false) {
	new scraperwiki();
}


class scraperwiki {

protected $db;

public function __construct($db = 'sqlite:scraperwiki.sqlite') {
	// connect
	scraperwiki::_connect($db);	
}


// set up the db connection
static function _connect($db = null) {
	if(empty($db)) {
		R::setup();	// for testing locally or where zero config is possible. Use like this: scraperwiki::_connect('');		
	} else {
		R::setup($db);	
	}
}


static function save($unique_keys = array(), $data, $table_name="swdata", $date = null) {
   $ldata = $data;   
   if (!is_null($date))
      $ldata["date"] = $date; 
   return scraperwiki::save_sqlite($unique_keys, $ldata, $table_name);
}


// A port of scraperwiki::save_sqlite from classic using RedbeanPHP

static function	save_sqlite($unique_keys = array(), $data, $table_name='swdata') {

    if (count($data) == 0)
        return;

	$table = R::dispense($table_name);	

     // convert special types
     foreach ($data as &$value) {
           if ($value instanceof DateTime) {
               $new_value = clone $value;
               $new_value->setTimezone(new DateTimeZone('UTC'));
               $value = $new_value->format(DATE_ISO8601);
               assert(strpos($value, "+0000") !== FALSE);
               $value = str_replace("+0000", "", $value);
           }
     }

    unset($value); // to fix the foreach pass by reference offset - http://www.php.net/manual/en/control-structures.foreach.php#113098

	// prepare an insert if we don't need to update
    foreach ($data as $key => $value) {
    	$table->$key = $value;
      // Don't do anything clever. Just always store everything as a string
      $table->setMeta('cast.'.$key, 'string');
    }

    // If this is the first row ever added, use this to create table and exit
	if (!R::$redbean->tableExists($table_name)) {
		
		// Define unique keys when creating table
		if(!empty($unique_keys)) {
 			$table->setMeta("buildcommand.unique", array($unique_keys));					
		}

		R::store($table);
		return true;
	}

	// if the table already exists and has unique keys, prepare an 'upsert' equivalent statement
    if(!empty($unique_keys)) {

        $parameters['table_name'] = $table_name;
        $parameters['keys'] = join(", ", array_keys($data)); 
        $parameters['values'] = join(', ', array_fill(0, count($data), '?')); // adds the ? placeholder for values

        $sql = vsprintf('INSERT or REPLACE INTO %s (%s) VALUES (%s)', $parameters);
        R::exec($sql,array_values($data));

		return true;

    } else {

		// if table already exists and doesn't have unique keys, just add this row
	    R::store($table);	                
		return true;
    }		


}

static function select($sql, $data = null) {
   if (is_null($data))
      $data = array();
   $result = R::getAll("SELECT ".$sql, $data);
   return $result;
}

static function save_var($name, $value) {	
   $vtype = gettype($value); 
   if (($vtype != "integer") && ($vtype != "string") && ($vtype != "double") && ($vtype != "NULL"))
      print_r("*** object of type $vtype converted to string\n"); 
   $data = array("name"=>$name, "value_blob"=>strval($value), "type"=>$vtype); 
   scraperwiki::save_sqlite(array("name"), $data, "swvariables"); 
}


static function get_var($name, $default=null) {

   $data = R::findOne('swvariables',
           ' name = ? ',array($name));

   if (!$data)
      return $default; 

   $svalue = $data->value_blob;
   $vtype = $data->type;

   if ($vtype == "integer")
      return intval($svalue); 
   if ($vtype == "double")
      return floatval($svalue); 
   if ($vtype == "NULL")
      return null;

   return $svalue; 
}


static function scrape($url) {
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
  // disable SSL checking to match behaviour in Python/Ruby.
  // ideally would be fixed by configuring curl to use a proper 
  // reverse SSL proxy, and making our http proxy support that.
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  $res = curl_exec($curl);
  curl_close($curl);
  return $res;
}



}


?>