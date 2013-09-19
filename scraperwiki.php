<?php

require 'rb.php';
R::setup('sqlite:scraperwiki.sqlite');


/*
 This is a first attempt at a RedbeanPHP approach to replicate basic functionality of scraperwiki::save_sqlite
*/

class scraperwiki {

	
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



static function	save_sqlite($unique_keys = array(), $data, $table_name='swdata') {

	$table = R::dispense($table_name);	

	// prepare an insert if we don't need to update
    foreach ($data as $key => $value) {
    	$table->$key = $value;
    }

    // If this is the first row ever added, use this to create table and exit
	if (!R::$redbean->tableExists($table_name)) {
		R::store($table);
		return true;
	}

	// if the table already exists and has unique keys, prepare an 'upsert' equivalent statement
    if(!empty($unique_keys)) {


        $wheres = '';
        foreach ($unique_keys as $unique) {
            $wheres .= $unique . " = '" . $data[$unique] . "' AND ";
        }
        $wheres = rtrim($wheres, ' AND ');

        $parameters['table_name'] = $table_name;
        $parameters['keys'] = join(", ", array_keys($data)); 
        $parameters['values'] = join(', ', array_fill(0, count($data), '?')); // adds the ? placeholder for values
        //$parameters['where'] = $wheres;	                

        $sql = vsprintf('INSERT or REPLACE INTO %s (%s) VALUES (%s)', $parameters);
        R::exec($sql,array_values($data));

		return true;

    } else {

		// if table already exists and doesn't have unique keys, just add this row
	    R::store($table);	                
		return true;
    }		


}

}


?>