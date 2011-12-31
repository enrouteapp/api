<?php

class Key extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database("default");
	}
	
	public function index()
	{
		$this->create(array());
	}
	
	public function create() 
	{
		$level = $this->request("level");
		$ignore_limits = $this->request("ignore_limits");

		$key = $this->key_generate();
		$level = $level ? $level : 1;
		$ignore_limits = $ignore_limits ? $ignore_limits : FALSE;

		// Insert the new key
		if ($this->key_insert($key,array("level" => $level, "ignore_limits" => $ignore_limits)))
		{
			return $this->response(array(
				"response" => "A new key with access level $level has been generated.",
				"key" => $key
			));
		}
		else
		{
			return $this->response(array(
				"response" => "A new key could not be generated."
			),TRUE);
		}
	}
	
	public function remove()
	{
		$key = $this->request("key");
		// check to see if the key exists
		if ( !$this->key_exists($key) )
		{
			return $this->response(array("response" => "Invalid API key!."),TRUE);
		}
		// delete it
		$this->key_delete($key);
		return $this->response(array("response" => "API key $key has been removed."));
	}
	
	public function modify()
	{
		$key = $this->request("key");
		$new_level = $this->request("level");
		// check to see if the key exists
		if ( !$this->key_exists($key) )
		{
			return $this->response(array("response" => "Invalid API key!."),TRUE);
		}
		// update the key
		if ( $this->key_update($key,array("level" => $new_level)) ) {
			return $this->response(array(
				"response" => "API key $key has been updated to permission level $new_level."
			));
		}
		else
		{
			return $this->response(array(
				"response" => "Unable to update key $key."
			),TRUE);
		}
	}
	
	public function disable() 
	{
		$this->modify(array("new_level" => 0));
	}
	
	public function enable()
	{
		$this->modify(array("new_level" => 1));
	}
	
	private function key_generate()
	{
		$this->load->helper('security');
		do {
			$salt = dohash(time().mt_rand());
			$new_key = substr($salt, 0, config_item("api_key_length"));
		} while ($this->key_exists($new_key));
		return $new_key;
	}
	
	private function key_exists($key) 
	{
		return $this->db->where("api_key",$key)->count_all_results("api_key") > 0;
	}
	
	private function key_get($key)
	{
		return $this->db->where("api_key",$key)->get("api_key")->row();
	}
	
	private function key_insert($key,$data)
	{
		$data["api_key"] = $key;
		$data["date_created"] = function_exists('now') ? now() : time();
		return $this->db->set($data)->insert("api_key");
	}
	
	private function key_update($key,$data)
	{
		return $this->db->where("api_key",$key)->update("api_key", $data);
	}
	
	private function key_delete($key)
	{
		return $this->db->where("api_key",$key)->delete("api_key");
	}
	
}
