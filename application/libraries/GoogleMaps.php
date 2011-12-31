<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class GoogleMaps {

	private $url_geocode = "http://maps.googleapis.com/maps/api/geocode/json";
	private $url_directions = "http://maps.googleapis.com/maps/api/directions/json";
	
	public function geocode($address1,$address2,$address3,$city,$state,$postal,$country)
	{
		// build address
		$address = $this->buildAddress($address1,$address2,$address3,$city,$state,$postal,$country);
		// build request
		$params = array(
			"address" => $address,
			"sensor" => "false"
		);
		// run request
		if ( $output = $this->run($this->url_geocode,$params) ) {
			if ( !isset($output["results"] ) ) return FALSE;
			foreach ( $output["results"] as $result ) {
				if ( !isset($result["geometry"]["location"]) ) return FALSE;
				$location = array(
					"lat" => $result["geometry"]["location"]["lat"],
					"lon" => $result["geometry"]["location"]["lng"]
				);
				// get additional components
				if ( isset($result["address_components"]) ) {
					foreach ( $result["address_components"] as $component ) {
						// postal code
						if ( in_array("postal_code",$component["types"]) ) {
							$location["postal"] = $component["short_name"];
						}
						// city
						if ( in_array("locality",$component["types"]) ) {
							$location["city"] = $component["short_name"];
						}
						// state
						if ( in_array("administrative_area_level_1",$component["types"]) ) {
							$location["state"] = $component["short_name"];
						}
						// country
						if ( in_array("country",$component["types"]) ) {
							$location["country"] = $component["short_name"];
						}
					}
				}
				return $location;
			}
		}
		else return FALSE;
	}
	
	public function getDuration($routes_raw)
	{
		foreach ( $routes_raw as $route ) {
			// sum up the total duration for all legs
			$duration = 0;
			foreach ( $route["legs"] as $leg ) {
				$duraction = $duration + intval($leg["duration"]["value"]);
			}
			return $duration;
		}
	}
	
	public function getRoute($stops,$optimize = FALSE)
	{
		if ( count($stops) < 2 ) return FALSE;
		// build origin
		if ( !isset($stops[0]["lat"],$stops[0]["lon"]) ) return FALSE;
		$origin = $stops[0]["lat"].",".$stops[0]["lon"];
		// build destination
		$last = count($stops)-1;
		if ( !isset($stops[$last]["lat"],$stops[$last]["lon"]) ) return FALSE;
		$destination = $stops[$last]["lat"].",".$stops[$last]["lon"];
		// build waypoints
		$waypoints = "";
		if ( count($stops) > 2 ) {
			foreach( array_slice($stops,1,-1) as $stop ) {
				
				if ( !isset($stop["lat"],$stop["lon"]) ) return FALSE;
				if ( strlen($waypoints) > 0 ) $waypoints = $waypoints."|";
				$waypoints = $waypoints.$stop["lat"].",".$stop["lon"];
			}
			if ( $optimize ) $waypoints = "optimize:true|".$waypoints;
		}
		// build request
		$params = array(
			"origin" => $origin,
			"destination" => $destination,
			"sensor" => "true"
		);
		if ( strlen($waypoints) > 0 ) $params["waypoints"] = $waypoints;
		// run request
		$output = $this->run($this->url_directions,$params);
		
		if ( !isset($output["routes"]) ) return FALSE;
		return $output["routes"];
	}
	
	private function run($url,$params)
	{
		$ci = &get_instance();
		$ci->config->load("api");
		// load the cache
		$ttl = $ci->config->item("google_api_ttl");
		$ci->load->driver('cache');
		$unique = md5(serialize(array("url" => $url, "params" => $params)));
		
		// try to load data from cache
		if ( !$data = $ci->cache->file->get($unique) ) {
		
			// prepare query
			$options = array(
				CURLOPT_POST => 0,
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1
			);
			// google doesn't accept post, we need to use GET grrrrrrr
			$vars = "";
			foreach ( $params as $key => $value ) {
				if ( strlen($vars) > 0 ) $vars .= "&";
				$vars .= ($key."=".$value);
			}
			$url = $url."?".$vars;
			// run query
			$session = curl_init($url);
			curl_setopt_array($session,$options);
			$result = curl_exec($session);
			if ( $result == false ) return FALSE; // host unreachable
			curl_close($session);
			// parse response
			$data = json_decode($result,TRUE);
			if ( $data == FALSE OR $data == NULL ) return FALSE; // invalid format
			if ( $data["status"] == "OVER_QUERY_LIMIT" ) die("WENT OVER GOOGLE API QUERY LIMIT! UNABLE TO CONTINUE.");
			if ( $data["status"] != "OK" ) return FALSE; // server error
			
			// save newly generated data to cache
			$ci->cache->file->save($unique,$data,$ttl) or die("Unable to save data to cache!.");
			//echo("  Loaded from scratch! ($unique)  ");
		}
		//else echo("  Loaded from cache! ($unique)  ");
		return $data;
			
	}
	
	private function buildAddress($address1,$address2,$address3,$city,$state,$postal,$country) 
	{
		$address = $address1;
		if ( strlen($address2) > 1 ) $address = $address.", ".$address2;
		if ( strlen($address3) > 1 ) $address = $address.", ".$address3;
		if ( strlen($city) > 1 ) $address = $address.", ".$city;
		if ( strlen($state) > 1 ) $address = $address.", ".$state;
		if ( strlen($postal) > 1 ) $address = $address.", ".$postal;
		if ( strlen($country) > 1 ) $address = $address.", ".$country;
		$address = str_replace(" ","+",$address);
		return $address;
	}
	
	// CACHING HELPERS
	
	private $cache_base = "/tmp/";
	private $cache_ttl = 60;
	
	private function cache_lookup($key) {
		//echo "searching for key $key...";
		$file = $this->cache_base.$key.".cache";
		$expire = time()-$this->cache_ttl;
		if ( file_exists($file) ) {
			if ( filemtime($file) > $expire ) {
				if ( filesize($file)  > 0 ) {
					//die("read in from cache");
					return unserialize(file_get_contents($file));
				}
			}
		}
		//die("unable to find in cache!");
		return FALSE;
	}
	
	private function cache_save($key,$data) {
		$fp = fopen($this->cache_base.$key.".cache","w") or die("Unable to write to cache!");
		fwrite($fp,serialize($data)) or die("unable to actually write to cache.");
		fclose($fp);
	}
	
}

?>