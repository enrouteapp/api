<?php

class Database extends CI_Controller
{
	public function index() 
	{
		// load the database
		$this->load->library("EnrouteDB");
		$this->load->helper("url");
		$schema = $this->enroutedb->current_schema();
		
		$data = array();
		$data["title"] = "enRoute Database Manager";
		$data["text"] = array(
			"The currently active database schema is shown in the arrays section below.",
			"Note: This data reflects what is currently ACTIVE in the actual database.");
		$data["links"] = array(
				"ACTIVE database schema" => site_url("/database/index"),
				"SAVED database schema" => site_url("/database/schema"),
				"Database problems? Repair now" => site_url("/database/repair"),
				"Database slow? Optimize now" => site_url("/database/optimize")
			);
		$data["arrays"] = array($schema);
		$this->load->view("generic",$data);
	}
	
	public function reset() 
	{
		// load and reset the database
		$this->load->library("EnrouteDB");
		$result = $this->enroutedb->reset();
		
		$data = array();
		$data["title"] = "enRoute Database Manager";
		$data["text"] = array("Results of the database reset operation are shown in the arrays section below.");
		$data["links"] = array(
				"ACTIVE database schema" => site_url("/database/index"),
				"SAVED database schema" => site_url("/database/schema"),
				"Database problems? Repair now" => site_url("/database/repair"),
				"Database slow? Optimize now" => site_url("/database/optimize")
			);
		$data["arrays"] = array($result);
		$this->load->view("generic",$data);
	}
	
	public function schema() 
	{
		// load the database
		$this->load->library("EnrouteDB");
		$this->load->helper("url");
		$schema = $this->enroutedb->saved_schema();
		
		$data = array();
		$data["title"] = "enRoute Database Manager";
		$data["text"] = array(
			"The currently saved database schema is shown in the arrays section below.",
			"Note: This data reflects the current SAVED database state as defined by the database library. The actual database may differ.");
		$data["links"] = array(
				"RESET database to reflect below schema (ALL DATA WILL BE LOST!)" => site_url("/database/reset"),
				"ACTIVE database schema" => site_url("/database/index"),
				"SAVED database schema" => site_url("/database/schema"),
				"Database problems? Repair now" => site_url("/database/repair"),
				"Database slow? Optimize now" => site_url("/database/optimize")
			);
		$data["arrays"] = array($schema);
		$this->load->view("generic",$data);
	}
	
	public function repair()
	{
		$this->load->library("EnrouteDB");
		$this->load->helper("url");
		$result = $this->enroutedb->repair();
		
		$data = array();
		$data["title"] = "enRoute Database Manager";
		$data["text"] = array(
			"The results of the per-table repair operations are shown below.");
		$data["links"] = array(
				"ACTIVE database schema" => site_url("/database/index"),
				"SAVED database schema" => site_url("/database/schema"),
				"Database problems? Repair now" => site_url("/database/repair"),
				"Database slow? Optimize now" => site_url("/database/optimize")
			);
		$data["arrays"] = array($result);
		$this->load->view("generic",$data);
	}
	
	public function optimize() 
	{
		$this->load->library("EnrouteDB");
		$this->load->helper("url");
		$result = $this->enroutedb->optimize();
		
		$data = array();
		$data["title"] = "enRoute Database Manager";
		$data["text"] = array(
			"The results of the per-table optimization operations are shown below.");
		$data["links"] = array(
				"ACTIVE database schema" => site_url("/database/index"),
				"SAVED database schema" => site_url("/database/schema"),
				"Database problems? Repair now" => site_url("/database/repair"),
				"Database slow? Optimize now" => site_url("/database/optimize")
			);
		$data["arrays"] = array($result);
		$this->load->view("generic",$data);
	}
		
}