<?php

class Route extends MY_Controller
{

	function index() 
	{
		//$this->details();
		$this->load->model("Route_Model","route");
		echo "<pre>";
		print_r($this->route->runRouting(2)); 
		echo "</pre>";
		exit();
	}
	
	public function create() 
	{
		$this->load->model("Route_Model","route");
		$data = array(
			"owner_id" => $this->request("owner_id"),
			"car_id" => $this->request("car_id"),
			"name" => $this->request("name"),
			"description" => $this->request("description"),
			"origin_id" => $this->request("origin_id"),
			"destination_id" => $this->request("destination_id")
		);
		if ( $id = $this->route->create($data) ) {
			return $this->response(array("response" => "Success!","route_id" => $id),FALSE);
		}
		else {
			return $this->response(array("response" => "This here shit is broken."),TRUE);
		}
	}
	
	public function remove() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		if ( $this->route->remove($route_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}	
		else {
			return $this->response(array("response" => "Unable to remove route with id $route_id."),TRUE);
		}
	}
	
	public function details() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		if ( $details = $this->route->details($route_id) ) {
			return $this->response(array("details" => $details),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to find route with id $route_id."),TRUE);
		}
	}
	
	public function modify() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		
		$data = array();
		// get fields to modify (note: all are optional)
		$optional = TRUE;
		if ( NULL != ( $owner_id = $this->request("owner_id",$optional) ) ) $data["owner_id"] = $owner_id;
		if ( NULL != ( $car_id = $this->request("car_id",$optional) ) ) $data["car_id"] = $car_id;
		if ( NULL != ( $name = $this->request("name",$optional) ) ) $data["name"] = $name;
		if ( NULL != ( $description = $this->request("description",$optional) ) ) $data["description"] = $description;
		if ( NULL != ( $origin_id = $this->request("origin_id",$optional) ) ) $data["origin_id"] = $origin_id;
		if ( NULL != ( $destination_id = $this->request("destination_id",$optional) ) ) $data["destination_id"] = $destination_id;
		
		if ( $this->route->modify($route_id,$data) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to update route with id $route_id."),TRUE);
		}
	}
	
	public function riderAdd() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		$rider_id = $this->request("rider_id");
		$place_id = $this->request("place_id",TRUE);
		$phone_id = $this->request("phone_id",TRUE);
		
		if ( $place_id == NULL AND $phone_id == NULL ) {
			return $this->response(array("response" => "EITHER a place_id OR phone_id must be provided."),TRUE);
		}
		
		if ( $this->route->riderAdd($route_id,$rider_id,$place_id,$phone_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to add rider $rider_id to route $route_id with place $place_id or phone $phone_id."),TRUE);
		}
	}
	
	public function riderRemove() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		$rider_id = $this->request("rider_id");
		
		if ( $this->route->riderRemove($route_id,$rider_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to remove rider $rider_id from route $route_id."),TRUE);
		}
	}
	
	public function riderList() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		
		if ( $riders = $this->route->riderList($route_id) ) {
			return $this->response(array("riders" => $riders),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to list riders for route $route_id."),TRUE);
		}
	}
	
	public function poiAdd() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		$place_id = $this->request("place_id");
		
		if ( $this->route->placeAdd($route_id,$place_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to add poi $place_id to route $route_id."),TRUE);
		}
	}
	
	public function poiRemove() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		$place_id = $this->request("place_id");
		
		if ( $this->route->placeRemove($route_id,$place_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to remove poi $place_id from route $route_id."),TRUE);
		}
	}
	
	public function stopList() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		
		if ( $result = $this->route->stopList($route_id) ) {
			return $this->response(array("stops" => $result["stops"], "mapdata" => $result["mapdata"], "cache" => $result["cache"]),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to list stops for route $route_id."),TRUE);
		}
	}
	
	public function stopOptimize()
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		
		if ( $this->route->stopOptimize($route_id) ) {
			$stops = $this->route->stopList($route_id);
			return $this->response(array("response" => "Success!", "stops" => $stops),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to optimize route $route_id."),TRUE);
		}
	}
	
	public function stopReorder() 
	{
		$this->load->model("Route_Model","route");
		$route_id = $this->request("route_id");
		
		$neworder = array();
		for ( $stop = 0; NULL != ($newpos = $this->request("stop".$stop,TRUE)); $stop++ ) {
			$neworder[$stop] = $newpos;
		}
		
		if ( $this->route->stopReorder($route_id,$neworder) ) {
			$stops = $this->route->stopList($route_id);
			return $this->response(array("response" => "Success!", "stops" => $stops),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to modify stop order for route $route_id.","neworder" => $neworder),TRUE);
		}
		
	}

}