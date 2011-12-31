<?php
class Debt_Model extends CI_Model {

    function __construct()
    {
        parent::__construct();
		$this->load->database("default");
    }
	
	function add($debtor_id,$creditor_id,$amount) 
	{
		$previous = $this->amount($debtor_id,$creditor_id);
		$this->remove($debtor_id,$creditor_id);
		$this->db->set("debtor_id",$debtor_id);
		$this->db->set("creditor_id",$creditor_id);
		$this->db->set("amount",$previous+$amount);
		if ( $this->db->insert("debt") ) {
			$last_id = $this->db->insert_id();
			return $last_id;
		}
		else return FALSE;
	}
	
	function remove($debtor_id,$creditor_id)
	{
		return $this->db->delete("debt",array("debtor_id" => $debtor_id, "creditor_id" => $creditor_id));
	}
	
	function amount($debtor_id,$creditor_id)
	{
		$this->db->select("*")->where("debtor_id",$debtor_id)->where("creditor_id",$creditor_id);
		$query = $this->db->get("debt");				  
						  
		$amount = 0;
		foreach ( $query->result() as $row ) {
			$amount += $row->amount;
		}
		return $amount;
	}
	
	function all($user_id)
	{
		$all = array();
		$this->db->select("*")->where("debtor_id",$user_id)->or_where("creditor_id",$user_id);
		$query = $this->db->get("debt");
		foreach ( $query->result() as $row ) {
			$all[] = $row;
		}
		return $all;
	}
	
}
?>