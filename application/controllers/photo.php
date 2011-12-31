<?php

class Photo extends MY_Controller
{

	function index() 
	{
		$this->download();
	}
	
	public function upload() 
	{
		// prepare to upload file
		$config = array();
		$config["upload_path"] = '/tmp/photo/';
		$config["allowed_types"] = 'jpg|jpeg';
		$config["max_size"] = '1024';
		$config["overwrite"] = TRUE;
		$this->load->library('upload', $config);
		// run the upload
		if ( $this->upload->do_upload("photo") ) {
			$photo = $this->upload->data();
			// open the file
			$this->load->helper("file");
			if ( $binary = read_file($photo["full_path"]) ) {
				// load the database
				$this->load->database("default");
				$data = array(
					"type" => $photo["image_type"],
					"mime" => $photo["file_type"],
					"width" => $photo["image_width"],
					"height" => $photo["image_height"],
					"size" => $photo["file_size"],
					"photo" => $binary
				);
				$result = $this->db->insert("photo",$data);
				// remove the temporary file
				delete_files($photo["full_path"]);
				// inform the end use
				if ( $result ) {
					return $this->response(array("photo_id" => $this->db->insert_id()));
				}
				else return $this->response(array("response" => "Unable to insert photo into database!"),TRUE);
			}
			else return $this->response(array("response" => "Unable to open uploaded photo!"),TRUE);
		}
		else {
			return $this->response(array(
				"response" => "Unable to upload photo! ".$this->upload->display_errors(),
				"info" => $this->upload->data()
				),TRUE);
		}

	}
	
	public function details() 
	{
		$photo_id = $this->request("photo_id");
		// retrieve the requested photo
		$this->load->database("default");
		if ( $query = $this->db->get_where("photo",array("photo_id" => $photo_id)) ) {
			if ( $photo_details = (array)$query->row() ) {
				$this->load->helper('url');
				$photo_details["link"] = site_url("/photo/download/photo_id/$photo_id");
				return $this->response(array("details" => $photo_details),TRUE);
			}
		}
		return $this->response(array("response" => "Unable to retrieve photo $photo_id!"),TRUE);
	}
	
	public function remove() 
	{
		$photo_id = $this->request("photo_id");
		// try to remove the requested photo
		$this->load->database("default");
		if ( $photo_id > 0 AND $this->db->delete("photo",array("photo_id" => $photo_id)) ) {
			return $this->response(array("response" => "Photo $photo_id has been removed."));
		}
		return $this->response(array("response" => "Unable to remove photo $photo_id!"),TRUE);
	}
	
	public function download() 
	{
		$photo_id = $this->request("photo_id");
		// retrieve the requested photo
		$this->load->database("default");
		if ( $query = $this->db->get_where("photo",array("photo_id" => $photo_id)) ) {
			if ( $photo = (array)$query->row() ) {
				// force a download (bypass normal output)
				$this->load->helper("download");
				force_download("image.".$photo["type"]."jpg",$photo["photo"]);
			}
		}
		// show a 404 error (bypass normal output)
		$this->load->helper("url");
		show_404(current_url());
	}

}