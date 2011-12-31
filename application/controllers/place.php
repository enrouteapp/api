<?php

class Place extends MY_Controller
{

	function index() 
	{
		$this->details();
	}
	
	public function create() 
	{
		$this->load->model("Place_Model","place");
		$optional = TRUE;
		$data = array(
			"name" => $this->request("name"),
			"address1" => $this->request("address1",$optional),
			"address2" => $this->request("address2",$optional),
			"address3" => $this->request("address3",$optional),
			"city" => $this->request("city",$optional),
			"state" => $this->request("state",$optional),
			"postal" => $this->request("postal",$optional),
			"country" => $this->request("country",$optional),
			"gps_lat" => $this->request("gps_lat",$optional),
			"gps_lon" => $this->request("gps_lon",$optional)
		);
		
		if ( $data["address1"] == NULL AND ($data["gps_lat"] == NULL OR $data["gps_lon"] == NULL) ) {
			return $this->response(array("response" => "You must provide either a GPS location or street address!"),TRUE);
		}
		
		
		if ( $id = $this->place->create($data) ) {
			return $this->response(array("response" => "Success!","place_id" => $id),FALSE);
		}
		else {
			return $this->response(array("response" => "This here shit is broken."),TRUE);
		}
	}
	
	public function remove() 
	{
		$this->load->model("Place_Model","place");
		$place_id = $this->request("place_id");
		if ( $this->place->remove($place_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}	
		else {
			return $this->response(array("response" => "Unable to remove place with id $place_id."),TRUE);
		}
	}
	
	public function details() 
	{
		$this->load->model("Place_Model","place");
		$place_id = $this->request("place_id");
		if ( $details = $this->place->details($place_id) ) {
			return $this->response(array("details" => $details),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to find place with id $place_id."),TRUE);
		}
	}
	
	public function modify() 
	{
		$this->load->model("Place_Model","place");
		$place_id = $this->request("place_id");
		
		$data = array();
		// get fields tomodify (note: all are optional)
		$optional = TRUE;
		if ( NULL != ( $name = $this->request("name",$optional))) $data["name"] = $name;
		if ( NULL != ( $address1 = $this->request("address1",$optional))) $data["address1"] = $address1;
		if ( NULL != ( $address2 = $this->request("address2",$optional))) $data["address2"] = $address2;
		if ( NULL != ( $address3 = $this->request("address3",$optional))) $data["address3"] = $address3;
		if ( NULL != ( $city = $this->request("city",$optional))) $data["city"] = $city;
		if ( NULL != ( $state = $this->request("state",$optional))) $data["state"] = $state;
		if ( NULL != ( $postal = $this->request("postal",$optional))) $data["postal"] = $postal;
		if ( NULL != ( $country = $this->request("country",$optional))) $data["country"] = $country;
		if ( NULL != ( $gps_lat = $this->request("gps_lat",$optional))) $data["gps_lat"] = $gps_lat;
		if ( NULL != ( $gps_lon = $this->request("gps_lon",$optional))) $data["gps_lon"] = $gps_lon;
		
		if ( $this->place->modify($place_id,$data) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to update place with id $place_id."),TRUE);
		}
	}
	
	public function photoAdd() 
	{
		$this->load->model("Place_Model","place");
		$place_id = $this->request("place_id");
		$photo_id = $this->request("photo_id");
		if ( $this->place->modify($place_id,array("photo_id" => $photo_id)) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to add photo for place with id $place_id."),TRUE);
		}
	}
	
	public function photoGet() 
	{
		$this->load->model("Place_Model","place");
		$place_id = $this->request("place_id");
		$details = $this->place->details($place_id);
		if ( $details["photo_id"] != NULL ) {
			$photo_id = $details["photo_id"];
			// redirect to the download location
			$this->load->helper('url');
			redirect("/photo/download/photo_id/$photo_id");
		}
		else return $this->response(array("response" => "No photo saved for this place!"),TRUE);
	}
	
}