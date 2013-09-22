<?php

require 'rb.php';
//R::setup('sqlite:scraperwiki.sqlite');



class scraperwiki {



static function save($unique_keys = array(), $data, $table_name="swdata", $date = null) {
   $ldata = $data;   
   if (!is_null($date))
      $ldata["date"] = $date; 
   return scraperwiki::save_sqlite($unique_keys, $ldata, $table); 
}


/*
 This is a first attempt at a RedbeanPHP approach to replicate basic functionality of scraperwiki::save_sqlite
*/

static function	save_sqlite($unique_keys = array(), $data, $table_name='swdata') {

    if (count($data) == 0)
        return;
    //if (!array_key_exists(0, $data))
    //    $data = array($data);

	$table = R::dispense($table_name);	

     // convert special types
     foreach ($data as $key => &$value) {
           if ($value instanceof DateTime) {
               $new_value = clone $value;
               $new_value->setTimezone(new DateTimeZone('UTC'));
               $value = $new_value->format(DATE_ISO8601);
               assert(strpos($value, "+0000") !== FALSE);
               $value = str_replace("+0000", "", $value);
           }
     }

	// prepare an insert if we don't need to update
    foreach ($data as $key => $value) {
    	$table->$key = $value;
    }

    // If this is the first row ever added, use this to create table and exit
	if (!R::$redbean->tableExists($table_name)) {
		if(!empty($unique_keys)) {
 			$table->setMeta("buildcommand.unique", array($unique_keys));					
		}

		R::store($table);
		return true;
	}

	// if the table already exists and has unique keys, prepare an 'upsert' equivalent statement
    if(!empty($unique_keys)) {

		$filter = array();
        $wheres = '';
        foreach ($unique_keys as $unique) {
            $wheres .= $unique . " = ? AND ";
			$filter[] = $data[$unique];
        }
        $wheres = rtrim($wheres, ' AND ');

        $parameters['table_name'] = $table_name;
        $parameters['keys'] = join(", ", array_keys($data)); 
        $parameters['values'] = join(', ', array_fill(0, count($data), '?')); // adds the ? placeholder for values
        //$parameters['where'] = $wheres;	                

        $sql = vsprintf('INSERT or REPLACE INTO %s (%s) VALUES (%s)', $parameters);

		echo $sql; echo '<br>'; print_r(array_values($data));
        R::exec($sql,array_values($data));

		return true;

    } else {

		// if table already exists and doesn't have unique keys, just add this row
	    R::store($table);	                
		return true;
    }		


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


	// get type
   	//     $bean->getMeta('type');
	
   if (!$data)
      return $default; 
   $svalue = $data->value_blob;; 
   //$vtype = $data[0][1];
   //if ($vtype == "integer")
   //   return intval($svalue); 
   //if ($vtype == "double")
   //   return floatval($svalue); 
   //if ($vtype == "NULL")
   //   return null;
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