<?php
class Message_Model extends CI_Model {

   function __construct()
    {
        parent::__construct();
		$this->load->database("default");
    }
	
	function create($sender_id,$recipient_id,$message)
	{
		$time = time();
		$this->db->set("sender_id",$sender_id);
		$this->db->set("recipient_id",$recipient_id);
		$this->db->set("message",$message);
		$this->db->set("timestamp",$time);
		if ( $this->db->insert("message") ) {
			$last_id = $this->db->insert_id();
			return $last_id;
		}
		else return FALSE;
	}
	
	function remove($message_id) 
	{
		return $this->db->delete("message",array("message_id" => $message_id));
	}
	
	function details($message_id)
	{
		$this->db->select("*")->where("message_id",$message_id);
		$details = $this->db->get("message")->result();
		if ( $details ) return (array)$details[0];
		else return FALSE;
	}
	
	function listAll($user_id) 
	{	
		$all = array();
		$this->db->select("*")->where("sender_id",$user_id)->or_where("recipient_id",$user_id);
		$query = $this->db->get("message");
		foreach ( $query->result() as $row ) {
			$all[] = (array)$row;
		}
		return $all;
	}
	
	function listSent($user_id)
	{						
		$all = array();
		$this->db->select("*")->where("sender_id",$user_id);
		$query = $this->db->get("message");
		foreach ( $query->result() as $row ) {
			$all[] = (array)$row;
		}
		return $all;
	}
	
	function listReceived($user_id)
	{
		$all = array();
		$this->db->select("*")->where("recipient_id",$user_id);
		$query = $this->db->get("message");
		foreach ( $query->result() as $row ) {
			$all[] = (array)$row;
		}
		return $all;
	}
	
}
?>