<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller 
{
	protected $request = NULL;
	protected $methods = array();
	private $internal = NULL;
	private $allow_access = TRUE;
	private $internal_use = FALSE;

	// list all supported formats, the first will be the default format
	private $supported_formats = array(
		'json' => 'application/json',
		'serialize' => 'application/vnd.php.serialized',
		'php' => 'text/plain',
		'html' => 'text/html',
		'csv' => 'application/csv'
	);
	
	// prevent API calls from using internal methods
	// underscored methods are blocked automatically
	protected $internal_methods = array(
		"request",
		"response",
		"valid_format",
		"check_api_key",
		"check_api_key_usage",
		"get_instance",
		"_remap",
		"__construct",
	);
	
	// API constructor
	// set API rqual to false for internal (non-API) use
	public function __construct($api = TRUE) 
	{
		parent::__construct();
		
		$this->benchmark->mark("construct");
		$this->request = array();
		$this->internal = array();
		
		if ( $api == TRUE ) 
		{
			// load config
			$this->load->config("api");
			
			// determine output format
			if ( $format = $this->request("format",TRUE) ) {
				if ( $this->valid_format($format) ) {
					$this->request["format"] = $format;
				}
			}
			else {
				// default to the first format entry
				$this->request["format"] = current(array_keys($this->supported_formats));
				// allow the default to be changed from the config file
				$format = config_item("api_default_format");
				if ( $format AND $this->valid_format($format) ) {
					$this->request["format"] = $format;
				}
			}
			
			// load database, if enabled
			if ( config_item("api_database_group") AND config_item("api_enable_keys") ) {
				$this->internal->db = $this->load->database(config_item("api_database_group"),TRUE);
			}
			
			// checking for keys? do so now
			if ( config_item("api_enable_keys") ) {
				$this->allow_access = check_api_key();
			}
		}
		else $this->internal_use = TRUE;
	}
	
	// dispatch request to the correct method
	public function _remap($method,$params = array())
	{
		// run key related operations	
		if ( config_item("api_enable_keys") ) 
		{
			// require key for this method?
			$require_key = !(isset($this->methods["method"]["key"]) AND ($this->methods["method"]["key"] === FALSE) );
		
			// check for bad key
			if ( $require_key AND $this->allow_access === FALSE ) {
				$this->response(array("error" => "Invalid API key. Access is denied."),TRUE);
				return;
			}
			
			// check usage limit
			if ( config_item("api_enable_limits") AND !$this->check_api_key_usage($method) ) {
				$this->response(array("error" => "API key has exceeded hourly usage limit for this method."),TRUE);
				return;
			}
			
			// compare permission levels
			$method_level = isset($this->methods[$method]['level']) ? $this->methods[$method]['level'] : 0;
			$key_level = $this->internal["level"];
			
			// key does not have the required permission level
			if ( $method_level > $key_level ) {
				$this->response(array("error" => "API key is not allowed to access this method. Permission level is too low."),TRUE);
				return;
			}
		}
		
		// prepare input data
		// start with an assiative array of the URI string
		$data = $this->uri->uri_to_assoc();
		// next, add the raw data provided
		//$data["raw"] = $params;
				
		// call requested method (don't allow access to internal methods)
		if ( method_exists($this,$method) && !in_array($method,$this->internal_methods) && $method != "index") {
			return call_user_func(array($this,$method),$data);
		}
		// invalid method, show a helpful error
		else {
			$this->response(array("message" => "Not a valid API end point."),TRUE);
			return;
		}
	}
	
	// read request data
	protected function request($key = "",$optional = FALSE) 
	{
		$params = array(); // FAKE IT
		$param = NULL;
        // first, check the request array
        if ( in_array($key,array_keys($this->request)) ) {
            $param = $this->request[$key];
        }
        // second, check the locally provided params
        if ( in_array($key,array_keys($params)) ) {
            $param = $params[$key];
        }
        // third, parse HTTP input data
        else {
            // check the URI
            $segments = $this->uri->uri_to_assoc();
            if ( in_array($key,array_keys($segments)) ) {
                $param = $segments[$key];
            }
            // check the GET and POST arrays
            else $param = $this->input->get_post($key,TRUE);
			
			//print_r($segments); exit();
        }
        // check for missing parameter
        if ( $param == NULL AND $optional == FALSE ) {
            return $this->response(array("response" => "Missing required parameter $key!"),TRUE);
        }
        else {
            $this->request[$key] = $param;
            return $param;
        }
	}
	
	// format and output method responses
	protected function response($data = array(),$error = FALSE,$http_code = 200)
	{
		$this->benchmark->mark("output");
		
		// internal use (just return data)
		//if ( $this->internal_use ) 
		//{
		//	return $data;
		//}
		// API use (output formatted data)
		//else 
		//{
			if ( empty($data) ) {
				$http_code = 404;
			}
			else {
				// check for error state and return status appropriately
				$data["status"] = 1;
				if ( $error ) $data["status"] = 0;
				// include some additional information
				$data["request"]= array();
				$data["request"]["controller"] = $this->router->class;
				$data["request"]["method"] = $this->router->method;
				$data["request"]["parameters"] = $this->request;
				$data["system"] = array();
				$data["system"]["execution_time"] = $this->benchmark->elapsed_time("construct","output");
				$data["system"]["execution_memory"] = (string)round(memory_get_usage()/1048576,4);
				// determine the correct output formatter
				$format = config_item("api_default_format");
				if ( isset($this->request["format"]) ) {
					if ( $this->request["format"] ) {
						$format = $this->request["format"];
					}
				}
				$output_formatter = "_format_".$format;
				// check to see if formatting method exists		
				if ( method_exists($this,$output_formatter) ) 
				{
					header("Content-type: ".$this->supported_formats[$format]);
					$output = $this->{$output_formatter}($data);
				}
				// format not supported, output directly
				else 
				{
					$output = $data;
				}	
			}
			header("HTTP/1.1: ".$http_code);
			header("Status: ".$http_code);
			exit($output);
		//}
	}
	
	// check for a valid outut format
	private function valid_format($format) 
	{
		return in_array($format,array_keys($this->supported_formats));
	}
	
	// check for a valid API key
	private function check_api_key() 
	{
		$key_name = config_item("api_key_name");
		$this->internal["key"] = NULL;
		$this->internal["level"] = NULL;
		$this->internal["ignore_limits"] = FALSE;
		// check to see if key was supplied
		if ( $key = request($key_name) ) {
			//retrieve key from database
			if ( $row = $this->internal->db->where("key",$key)->get(config_item("api_keys_table"))->row() ) {
				$this->internal["key"] = $row->key;
				$this->internal["level"] = $row->level;
				$this->internal["ignore_limits"] = $row->ignore_limits;
				return TRUE;
			}
		}
		// no key has been set
		return FALSE;
	}
	
	// check API key usage
	private function check_api_key_usage($method)
	{
		// ignore limit
		if ( !empty($this->internal["ignore_limits"]) OR !isset($this->methods[$method]['limit']) ) {
			return TRUE;
		}
		
		// check hourly usage limit
		$limit = $this->methods[$method]["limit"];
		
		// get data for this API key
		$result = $this->internal->db
						->where('uri', $this->uri->uri_string())
						->where('api_key', $this->internal->key)
						->get(config_item('rest_limits_table'))
						->row();
						
		// no calls yet, or has been more than an hour since last use
		if ( !$result OR $result->hour_started < time()-(60*60)) 
		{
			// setup a new entry
			$this->internal->db->insert('limits', array(
				'uri' => $this->uri->uri_string(),
				'api_key' => isset($this->internal->key) ? $this->internal->key : '',
				'count' => 1,
				'hour_started' => time()
			));
		}
		// key has been used within the last hour
		else {
			// limit exceeded
			if ( $result->count > $limit ) return FALSE;
			$this->rest->db
					->where('uri', $this->uri->uri_string())
					->where('api_key', $this->internal->key)
					->set('count', 'count + 1', FALSE)
					->update(config_item('limits'));
			
		}
		// allow access
		return TRUE;		
	}
	
	//
	// OUTPUT FORMAT FUNCTIONS
	//
	
	// Format HTML for output
	private function _format_html($data = array())
	{
		// Multi-dimentional array
		if (isset($data[0]))
		{
			$headings = array_keys($data[0]);
		}

		// Single array
		else
		{
			$headings = array_keys($data);
			$data = array($data);
		}

		$this->load->library('table');

		$this->table->set_heading($headings);

		foreach ($data as &$row)
		{
			$this->table->add_row($row);
		}

		return $this->table->generate();
	}

	// Format HTML for output
	private function _format_csv($data = array())
	{
		// Multi-dimentional array
		if (isset($data[0]))
		{
			$headings = array_keys($data[0]);
		}

		// Single array
		else
		{
			$headings = array_keys($data);
			$data = array($data);
		}

		$output = implode(',', $headings) . "\r\n";
		foreach ($data as &$row)
		{
			$output .= '"' . implode('","', $row) . "\"\r\n";
		}

		return $output;
	}

	// Encode as JSON
	private function _format_json($data = array())
	{
		return json_encode($data);
	}

	// Encode as Serialized array
	private function _format_serialize($data = array())
	{
		return serialize($data);
	}

	// Encode raw PHP
	private function _format_php($data = array())
	{
		return var_export($data, TRUE);
	}

}