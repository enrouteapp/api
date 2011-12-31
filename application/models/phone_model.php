<?php
class Phone_Model extends CI_Model {

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
		if ( $this->db->insert("phone") ) {
			$last_id = $this->db->insert_id();
			return $last_id;
		}
		else return FALSE;
	}
	
	function remove($phone_id) 
	{
		return $this->db->delete("phone",array("phone_id" => $phone_id));
	}
	
	function details($phone_id)
	{
		$this->db->select("*")->where("phone_id",$phone_id);
		$details = $this->db->get("phone")->result();
		if ( $details ) return (array)$details[0];
		else return FALSE;
	}
	
	function modify($phone_id,$data = array())
	{
		foreach ( $data as $key => $value ) {
			//TODO: ERROR CHECKING HERE
			$this->db->set($key,$value);
		}
		return $this->db->where("phone_id",$phone_id)->update("phone");
	}
	
	function locationUpdate($phone_id,$gps_lat,$gps_lon)
	{
		$timestamp = time();
		if ( $gps_lat < -90 OR $gps_lat > 90 ) return FALSE;
		if ( $gps_lon < -180 OR $gps_lon > 180 ) return FALSE;
		$this->db->set("phone_id",$phone_id);
		$this->db->set("timestamp",$timestamp);
		$this->db->set("gps_lat",$gps_lat);
		$this->db->set("gps_lon",$gps_lon);
		return $this->db->insert("phone_history");
	}
	
	function locationGet($phone_id)
	{
		$time = time();
		$history = $this->locationHistory($phone_id,0,$time);
		if ( !isset($history[0]) ) return FALSE;
		else return $history[0];
	}
	
	function locationHistory($phone_id,$min_timestamp = NULL,$max_timestamp = NULL)
	{
		$this->db->select("*")->where("phone_id",$phone_id);
		if ( $min_timestamp != NULL ) $this->db->where("timestamp >=",$min_timestamp);
		if ( $max_timestamp != NULL ) $this->db->where("timestamp <=",$max_timestamp);
		$query = $this->db->order_by("timestamp","desc")->get("phone_history");
		$all = array();
		foreach ( $query->result() as $row ) {
			$all[] = (array)$row;
		}
		return $all;
	}
	
}
?>