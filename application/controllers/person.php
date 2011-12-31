<?php

class Person extends MY_Controller
{

	function index() 
	{
		$this->details();
	}
	
	public function create() 
	{
		$this->load->model("Person_Model","person");
		$this->load->library("encrypt");
		$data = array(
			"fullname" => $this->request("fullname"),
			"email" => $this->request("email"),
			"password" => $this->password_encrypt($this->request("password")),
			"date_created" => time(),
			"date_last_seen" => time()
		);		
		if ( $id = $this->person->create($data) ) {
			return $this->response(array("response" => "Success!","person_id" => $id),FALSE);
		}
		else {
			return $this->response(array("response" => "This here shit is broken."),TRUE);
		}
	}
	
	public function remove() 
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		if ( $this->person->remove($person_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}	
		else {
			return $this->response(array("response" => "Unable to remove person with id $person_id."),TRUE);
		}
	}
	
	public function details() 
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		if ( $details = $this->person->details($person_id) ) {
			return $this->response(array("details" => $details),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to find person with id $person_id."),TRUE);
		}
	}
	
	public function modify() 
	{
		$this->load->model("Person_Model","person");
		$this->load->library("encrypt");
		$person_id = $this->request("person_id");
		
		$data = array();
		// get fields to modify (note: all are optional)
		$optional = TRUE;
		if ( NULL != ( $fullname = $this->request("fullname",$optional) ) ) $data["fullname"] = $fullname;
		if ( NULL != ( $email = $this->request("email",$optional) ) ) $data["email"] = $email;
		if ( NULL != ( $password = $this->request("password",$optional) ) ) $data["password"] = $this->password_encrypt($password);
		if ( NULL != ( $place_id = $this->request("place_id",$optional) ) ) $data["place_id"] = $place_id;
		if ( NULL != ( $phone_id = $this->request("phone_id",$optional) ) ) $data["phone_id"] = $fullname;
		if ( NULL != ( $ready = $this->request("ready",$optional) ) ) $data["ready"] = $ready;
		$data["date_last_seen"] = time();
		
		if ( $this->person->modify($person_id,$data) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to update person with id $person_id."),TRUE);
		}
	}
	
	public function login()
	{
		$this->load->model("Person_Model","person");
		$this->load->library("encrypt");
		$email = $this->request("email");
		$password_user_clear = $this->request("password");
		if ( $details = $this->person->details_email($email) ) {
			$password_crypt = $details["password"];
			$password_user_crypt = $this->password_encrypt($password_user_clear);
			if ( $password_crypt == $password_user_crypt ) {
				$timestamp = time();
				$this->person->modify($details["person_id"],array("date_last_seen" => $timestamp));
				return $this->response(array("response" => "Success!", "details" => $details));
			}
			else {
				return $this->response(array("response" => "Authorization failure. Email and password do not match."),TRUE);
			}
		}
		else {
			return $this->response(array("response" => "Unable to find person with email $email."),TRUE);
		}
	}
	
	public function phoneLink() 
	{
		$person_id = $this->request("person_id");
		$phone_id = $this->request("phone_id");
		return $this->modify();
	}
	
	public function phoneUnlink() 
	{
		$person_id = $this->request("person_id");
		return $this->modify();
	}
	
	public function placeDefaultLink()
	{
		$person_id = $this->request("person_id");
		$place_id = $this->request("place_id");
		return $this->modify($person_id,array("place_id" => $place_id));
	}
	
	public function placeDefaultUnlink()
	{
		$person_id = $this->request("person_id");
		return $this->modify(array("person_id" => $person_id, "place_id" => NULL));
	}
	
	public function placeFaveAdd() 
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		$place_id = $this->request("place_id");
		
		if ( $this->person->placeFaveAdd($person_id,$place_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to add favorite place $place_id for person $person_id."),TRUE);
		}
	}
	
	public function placeFaveRemove() 
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		$place_id = $this->request("place_id");
		
		if ( $this->person->placeFaveRemove($person_id,$place_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to remove favorite place $place_id for person $person_id."),TRUE);
		}
	}
	
	public function placeFaveList() 
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		
		if ( $places = $this->person->placeFaveList($person_id) ) {
			return $this->response(array("places" => $places),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to retrieve favorite places for person $person_id."),TRUE);
		}
	}
	
	public function photoAdd() 
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		$photo_id = $this->request("photo_id");
		if ( $this->person->modify($person_id,array("photo_id" => $photo_id)) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to add photo for person with id $person_id."),TRUE);
		}
	}
	
	public function photoGet() 
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		$details = $this->person->details($person_id);
		if ( $details["photo_id"] != NULL ) {
			$photo_id = $details["photo_id"];
			// redirect to the download location
			$this->load->helper('url');
			redirect("/photo/download/photo_id/$photo_id");
		}
		else return $this->response(array("response" => "No photo saved for this person!"),TRUE);
	}
	
	public function isReady()
	{
		$this->load->model("Person_Model","person");
		$person_id = $this->request("person_id");
		$details = $this->person->details($person_id);
		if ( $details["ready"] ) return $this->response(array("ready" => TRUE),FALSE);
		else return $this->response(array("ready" => FALSE),FALSE);
	}
	
	public function ready()
	{
		$person_id = $this->request("person_id");
		$ready = $this->request("ready");
		return $this->modify();
	}

	private function password_encrypt($password)
	{
		return sha1($password.$this->config->item('encryption_key'));
	}

}