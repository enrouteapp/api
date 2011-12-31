<?php

class Car extends MY_Controller
{

	function index() 
	{
		$this->details();
	}
	
	public function create() 
	{
		$this->load->model("Car_Model","car");
		$data = array(
			"name" => $this->request("name"),
			"make" => $this->request("make"),
			"model" => $this->request("model"),
			"color" => $this->request("color"),
			"owner_id" => $this->request("owner_id")
		);
		if ( $id = $this->car->create($data) ) {
			return $this->response(array("response" => "Success!","car_id" => $id),FALSE);
		}
		else {
			return $this->response(array("response" => "This here shit is broken."),TRUE);
		}
	}
	
	public function remove() 
	{
		$this->load->model("Car_Model","car");
		$car_id = $this->request("car_id");
		if ( $this->car->remove($car_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}	
		else {
			return $this->response(array("response" => "Unable to remove car with id $car_id."),TRUE);
		}
	}
	
	public function details() 
	{
		$this->load->model("Car_Model","car");
		$car_id = $this->request("car_id");
		if ( $details = $this->car->details($car_id) ) {
			return $this->response(array("details" => $details),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to find car with id $car_id."),TRUE);
		}
	}
	
	public function modify() 
	{
		$this->load->model("Car_Model","car");
		$car_id = $this->request("car_id");
		
		$data = array();
		// get fields to modify (note: all are optional)
		$optional = TRUE;
		if ( NULL != ( $name = $this->request("name",$optional) ) ) $data["name"] = $name; 
		if ( NULL != ( $make = $this->request("make",$optional) ) ) $data["make"] = $make;
		if ( NULL != ( $model = $this->request("model",$optional) ) ) $data["model"] = $model;
		if ( NULL != ( $owner_id = $this->request("owner_id",$optional) ) ) $data["owner_id"] = $owner_id;
		if ( NULL != ( $photo_id = $this->request("photo_id",$optional) ) ) $data["photo_id"] = $photo_id;
		
		if ( $this->car->modify($car_id,$data) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to update car with id $car_id."),TRUE);
		}
	}
	
	public function photoAdd() 
	{
		$car_id = $this->request("car_id");
		$photo_id = $this->request("photo_id");
		$this->modify(array("car_id" => $car_id, "photo_id" => $photo_id));
	}
	
	public function photoGet() 
	{
		$this->load->model("Car_Model","car");
		$car_id = $this->request("car_id");
		$details = $this->car->details($car_id);
		if ( $details["photo_id"] != NULL ) {
			$photo_id = $details["photo_id"];
			// redirect to the download location
			$this->load->helper('url');
			redirect("/photo/download/photo_id/$photo_id");
		}
		else return $this->response(array("response" => "No photo saved for this car!"),TRUE);
	}
	
	public function locationUpdate() 
	{
		$this->load->model("Car_Model","car");
		$car_id = $this->request("car_id");
		$gps_lat = $this->request("gps_lat");
		$gps_lon = $this->request("gps_lon");
		
		if ( $result = $this->car->locationUpdate($car_id,$gps_lat,$gps_lon) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to update location for car id $car_id."),TRUE);
		}
	}
	
	public function locationGet() 
	{
		$this->load->model("Car_Model","car");
		$car_id = $this->request("car_id");
		
		if ( $result = $this->car->locationGet($car_id) ) {
			return $this->response(array("location" => $result),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to get location for car id $car_id."),TRUE);
		}
	}
	
	public function locationHistory()
	{
		$this->load->model("Car_Model","car");
		$phone_id = $this->request("car_id");
		
		$optional = TRUE;
		$min_timestamp = $this->request("min_timestamp",$optional);
		$max_timestamp = $this->request("max_timestamp",$optional);
		
		if ( $result = $this->car->locationHistory($car_id,$min_timestamp,$max_timestamp) ) {
			return $this->response(array("location" => $result),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to get location history for car id $car_id with min $min_timestamp and max $max_timestamp."),TRUE);
		}
	}

}