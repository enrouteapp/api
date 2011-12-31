<?php

class Phone extends MY_Controller
{

	function index() 
	{
		$this->details();
	}
	
	public function create() 
	{
		$this->load->model("Phone_Model","phone");
		$data = array(
			"carrier" => $this->request("carrier"),
			"number" => $this->request("number")
		);
		if ( $id = $this->phone->create($data) ) {
			return $this->response(array("response" => "Success!","phone_id" => $id),FALSE);
		}
		else {
			return $this->response(array("response" => "This here shit is broken."),TRUE);
		}
	}
	
	public function remove() 
	{
		$this->load->model("Phone_Model","phone");
		$phone_id = $this->request("phone_id");
		if ( $this->phone->remove($phone_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}	
		else {
			return $this->response(array("response" => "Unable to remove phone with id $phone_id."),TRUE);
		}		
	}
	
	public function details() 
	{
		$this->load->model("Phone_Model","phone");
		$phone_id = $this->request("phone_id");
		if ( $details = $this->phone->details($phone_id) ) {
			return $this->response(array("details" => $details),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to find person with id $phone_id."),TRUE);
		}
	}
	
	public function modify() 
	{
		$this->load->model("Phone_Model","phone");
		$phone_id = $this->request("phone_id");
		
		$data = array();
		// get fields to modify (note: all are optional)
		$optional = TRUE;
		if ( NULL != ( $carrier = $this->request("carrier",$optional) ) ) $data["carrier"] = $carrier;
		if ( NULL != ( $number = $this->request("number",$optional) ) ) $data["number"] = $number;
		
		if ( $this->phone->modify($phone_id,$data) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to update phone with id $phone_id."),TRUE);
		}
		
	}
	
	public function locationUpdate() 
	{
		$this->load->model("Phone_Model","phone");
		$phone_id = $this->request("phone_id");
		$gps_lat = $this->request("gps_lat");
		$gps_lon = $this->request("gps_lon");
		
		if ( $result = $this->phone->locationUpdate($phone_id,$gps_lat,$gps_lon) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to update location for phone id $phone_id."),TRUE);
		}
	}
	
	public function locationGet() 
	{
		$this->load->model("Phone_Model","phone");
		$phone_id = $this->request("phone_id");
		
		if ( $result = $this->phone->locationGet($phone_id) ) {
			return $this->response(array("location" => $result),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to get location for phone id $phone_id."),TRUE);
		}
	}
	
	public function locationHistory()
	{
		$this->load->model("Phone_Model","phone");
		$phone_id = $this->request("phone_id");
		
		$optional = TRUE;
		$min_timestamp = $this->request("min_timestamp",$optional);
		$max_timestamp = $this->request("max_timestamp",$optional);
		
		if ( $result = $this->phone->locationHistory($phone_id,$min_timestamp,$max_timestamp) ) {
			return $this->response(array("location" => $result),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to get location history for phone id $phone_id with min $min_timestamp and max $max_timestamp."),TRUE);
		}
	}
	

}