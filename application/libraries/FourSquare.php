<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

// NOT FOR ENROUTE USE
// push phone locations to Foursquare
// refer to https://developer.foursquare.com/docs/overview.html

class FourSquare {

	private $url_api_v1 = "https://api.foursquare.com/v1";
	
	public function checkin($username,$password,$message,$lat,$lon) 
	{
		// find a nearby venue
		$venues = $this->venue_search($username,$password,$lat,$lon);
		
		if ( $venues == FALSE ) return FALSE;
		$distance = 9999;
		$venue_id = 0;
		foreach ( $venues as $venue ) {
			if ( $venue["distance"] < $distance ) {
				$distance = $venue["distance"];
				$venue_id = $venue["id"];
			}
		}
		
		// checkin
		$params = array(
			"shout" => $message,
			"vid" => $venue_id,
			"geolat" => $lat,
			"geolong" => $lon
		);
		return $this->run($username,$password,"checkin.json",$params);

	}
	
	public function venue_search($username,$password,$lat,$lon)
	{
		$params = array(
			"geolat" => $lat,
			"geolong" => $lon
		);
		$result = $this->run($username,$password,"venues.json",$params);		
		if ( $result == FALSE ) return FALSE;
		return $result["groups"][0]["venues"];
		
	}
	
	private function run($username,$password,$path,$params)
	{
		if ( $path == "checkin.json" ) {
			$url = $this->url_api_v1."/$path?";
			$options = array(
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_USERPWD => $username.":".$password,
				CURLOPT_POSTFIELDS => http_build_query($params)
			);
		}
		else {
			$url = $this->url_api_v1."/$path?".http_build_query($params);			
			$options = array(
				CURLOPT_POST => 0,
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_USERPWD => $username.":".$password
			);
		}
		$ch = curl_init($url);
		
		curl_setopt_array($ch,$options);
		// run the request
		if ( !$result = curl_exec($ch) ) return FALSE;
		curl_close($ch);
		// parse the response		
		$data = json_decode($result,TRUE);
		return $data;
	}

}

?>