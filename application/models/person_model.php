<?php
class Person_Model extends CI_Model {

    function __construct()
    {
        parent::__construct();
		$this->load->database("default");
    }
	
	function create($data)
	{
		foreach ( $data as $key => $value ) {
			// TODO: ERROR CHECKING HERE
			$this->db->set($key,$value);
		}
		if ( $this->db->insert("person") ) {
			$last_id = $this->db->insert_id();
			return $last_id;
		}
		else return FALSE;
	}
	
	function remove($person_id) 
	{
		return $this->db->delete("person",array("person_id" => $person_id));
	}
	
	function details($person_id)
	{
		$this->db->select("*")->where("person_id",$person_id);
		$details = $this->db->get("person")->result();
		if ( $details ) return (array)$details[0];
		else return FALSE;
	}
	
	function details_email($email)
	{
		$this->db->select("*")->where("email",$email);
		$details = $this->db->get("person")->result();
		if ( $details ) return (array)$details[0];
		else return FALSE;
	}
	
	function modify($person_id,$data = array())
	{
		foreach ( $data as $key => $value ) {
			//TODO: ERROR CHECKING HERE
			$this->db->set($key,$value);
		}
		return $this->db->where("person_id",$person_id)->update("person");
	}
	
	function placeFaveAdd($person_id,$place_id) 
	{
		$this->db->set("person_id",$person_id);
		$this->db->set("place_id",$place_id);
		if ( $this->db->insert("fave_places") ) return TRUE;
		else return FALSE;
	}
	
	function placeFaveRemove($person_id,$place_id)
	{
		return $this->db->delete("fave_places",array("person_id" => $person_id, "place_id" => $place_id));
	}
	
	function placeFaveList($person_id)
	{
		$this->db->select("*")->where("person_id",$person_id);
		$places = $this->db->get("fave_places")->result();
		$all = array();
		foreach ( $places as $place ) {
			$all[] = (array)$place;
		}
		return $all;
	}
	
}
?>