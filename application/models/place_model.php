<?php
class Place_Model extends CI_Model {

    function __construct()
    {
        parent::__construct();
		$this->load->database("default");
    }
	
	function create($data)
	{
		$geocode = TRUE;
		foreach ( $data as $key => $value ) {
			//TODO: ERROR CHECKING HERE
			$this->db->set($key,$value);
			// check if GPS coordinated have been provided
			if ( $key == "gps_lat" AND $value != "0" ) $geocode = FALSE;
			if ( $key == "gps_lon" AND $value != "0" ) $geocode = FALSE;
		}
		if ( $this->db->insert("place") ) {
			$last_id = $this->db->insert_id();
			if ( $geocode AND !$this->geocode($last_id) ) return FALSE;
			else return $last_id;
		}
		else return FALSE;
	}
	
	function remove($place_id) 
	{
		return $this->db->delete("place",array("place_id" => $place_id));
	}
	
	function details($place_id)
	{
		$this->db->select("*")->where("place_id",$place_id);
		$details = $this->db->get("place")->result();
		if ( $details ) return (array)$details[0];
		else return FALSE;
	}
	
	function modify($place_id,$data = array())
	{
		$geocode = FALSE;
		$update = FALSE;
		foreach ( $data as $key => $value ) {
			$update = TRUE;
			//TODO: ERROR CHECKING HERE
			$this->db->set($key,$value);
			// check if re-geocode is necessary
			if ( in_array($key,array("address1","address2","address3","city","state","postal","country")) ) {
				$geocode = TRUE;
			}
		}
		// update place
		if ($update AND !$this->db->where("place_id",$place_id)->update("place")) return FALSE;
		// re-geocode, if necessary
		if ( $geocode AND !$this->geocode($place_id) ) return FALSE;
		else return TRUE;
	}
	
	private function geocode($place_id)
	{
		if ( $details = $this->details($place_id) ) {
			$address1 = $details["address1"];
			$address2 = $details["address2"];
			$address3 = $details["address3"];
			$city = $details["city"];
			$state = $details["state"];
			$postal = $details["postal"];
			$country = ($details["country"]) ? $details["country"] : "USA";
			// run geocode query
			$this->load->library("GoogleMaps");
			$location = $this->googlemaps->geocode($address1,$address2,$address3,$city,$state,$postal,$country);
			if ( $location ) {
				$data = array(
					"gps_lat" => $location["lat"],
					"gps_lon" => $location["lon"]
				);
				// set additional blank components
				if ( strlen($details["postal"]) < 2 AND isset($location["postal"]) ) {
					$data["postal"] = $location["postal"];
				}
				if ( strlen($details["city"]) < 2 AND isset($location["city"]) ) {
					$data["city"] = $location["city"];
				}
				if ( strlen($details["state"]) < 2 AND isset($location["state"]) ) {
					$data["state"] = $location["state"];
				}
				if ( strlen($details["country"]) < 2 AND isset($location["country"]) ) {
					$data["country"] = $location["country"];
				}
				// update
				if ($this->modify($place_id,$data)) return TRUE;
				else return FALSE;
			}
			else return FALSE;
		}
		else return FALSE;
	}

	
}
?>