<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Contents extends MY_Controller {

	function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		return $this->response(array(
			"message" => "Not a valid API end point."
			),TRUE);
		//return $this->response(array(
		//	"message" => "This page shows a summary of all accessible controllers and methods.",
		//	"controllers" => $this->controllers()
		//	));
	}
	
	// list all controllers and associated methods
	private function controllers()
	{
		$controller_path = FCPATH.APPPATH."controllers";
		$this->load->helper("directory");
		$listing = directory_map($controller_path);
		
		$controllers = array();
		foreach ( $listing as $file ) {
			// don't include the welcome controller
			if ( !in_array($file,array("contents.php")) ) {
				// check if current file is an array
				if ( is_array($file) ) {
					foreach ( $file as $actual_file ) {
						$controllers[] = $this->format_controller_methods($controller_path,$actual_file);
					}
				}
				// prepare class information
				$controllers[] = $this->format_controller_methods($controller_path,$file);
			}
		}
		// remove empty entries
		foreach ( array_keys($controllers) as $key ) {
			if ( empty($controllers[$key]) ) unset($controllers[$key]);
		}
		// sort controllers 
		sort($controllers);
		return $controllers;
		
	}

	private function format_controller_methods($path,$file) {
		$this->load->helper("url");
		$controller = array();
		// only show php files
		if ( ($extension = substr($file,strrpos($file,".")+1)) == "php" ) {
			// include the class
			include_once($path."/".$file);
			$parts = explode(".",$file);
			$class_lower = $parts["0"];
			$class = ucfirst($class_lower);
			// check if a class actually exists
			if ( class_exists($class) AND get_parent_class($class) == "MY_Controller" ) {
				// get a list of all methods
				$controller["name"] = $class;
				$controller["path"] = base_url().$class_lower;
				$controller["methods"] = array();
				// get a list of all public methods				
				foreach ( get_class_methods($class) as $method ) {
					$reflect = new ReflectionMethod($class, $method);
					if ( $reflect->isPublic() ) {
						// ignore some methods
						$object = new $class();
						if ( !in_array($method,$object->internal_methods) ) {
							$method_array = array();
							$method_array["name"] = $method;
							$method_array["path"] = base_url().$class_lower."/".$method;
							$controller["methods"][] = $method_array;
						}
					}
				}
			}
		}
		return $controller;
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */