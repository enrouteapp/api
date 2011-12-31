<?php

class Debt extends MY_Controller
{

	function index() 
	{
		$this->listAll();
	}
	
	public function add() 
	{
		$this->load->model("Debt_Model","debt");
		$data = array(
			"debtor_id" => $this->request("debtor_id"),
			"creditor_id" => $this->request("creditor_id"),
			"amount" => $this->request("amount")
		);
		if ( $id = $this->debt->create($data) ) {
			return $this->response(array("response" => "Success!","debt_id" => $id),FALSE);
		}
		else {
			return $this->response(array("response" => "This here shit is broken."),TRUE);
		}
	}
	
	public function amount()
	{
		$this->load->model("Debt_Model","debt");
		$debtor_id = $this->request("debtor_id");
		$creditor_id = $this->request("creditor_id");
		if ( $amount = $this->debt->amount($creditor_id,$debtor_id) ) {
			return $this->response(array("amount" => $amount),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to calculate total debt between creditor id $creditor_id and debtor id $debtor_id."),TRUE);
		}
	}
	
	public function resolve() 
	{
		$this->load->model("Debt_Model","debt");
		$debtor_id = $this->request("debtor_id");
		$creditor_id = $this->request("creditor_id");
		if ( $this->debt->remove($creditor_id,$debtor_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to resolve debts between creditor id $creditor_id and debtor id $debtor_id."),TRUE);
		}
	}
	
	public function listAll() 
	{
		$this->load->model("Debt_Model","debt");
		$person_id = $this->request("person_id");
		if ( $debts = $this->debt->all($person_id) ) {
			return $this->response(array("debts" => $debts),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to retrieve debts for person $person_id."),TRUE);
		}
	}

}