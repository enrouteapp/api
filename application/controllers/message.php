<?php

class Message extends MY_Controller
{

	function index() 
	{
		$this->details();
	}
	
	public function send() 
	{
		$this->load->model("Message_Model","message");
		$data = array(
			"sender_id" => $this->request("sender_id"),
			"recipient_id" => $this->request("recipient_id"),
			"message" => $this->request("message")
		);
		if ( $id = $this->message->create($data["sender_id"],$data["recipient_id"],$data["message"]) ) {
			return $this->response(array("response" => "Success!","message_id" => $id),FALSE);
		}
		else {
			return $this->response(array("response" => "This here shit is broken."),TRUE);
		}
	}
	
	public function remove() 
	{
		$this->load->model("Message_Model","message");
		$message_id = $this->request("message_id");
		if ( $this->message->remove($person_id) ) {
			return $this->response(array("response" => "Success!"),FALSE);
		}	
		else {
			return $this->response(array("response" => "Unable to remove message with id $message_id."),TRUE);
		}
	}
	
	public function details() 
	{
		$this->load->model("Message_Model","message");
		$message_id = $this->request("message_id");
		if ( $details = $this->message->details($message_id) ) {
			return $this->response(array("details" => $details),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to find message with id $message_id."),TRUE);
		}
	}
	
	public function listAll() 
	{
		$this->load->model("Message_Model","message");
		$person_id = $this->request("person_id");
		if ( $messages = $this->message->listAll($user_id) ) {
			return $this->response(array("messages" => $messages),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to load messages for person id $person_id."),TRUE);
		}
	}
	
	public function listSent() 
	{
		$this->load->model("Message_Model","message");
		$sender_id = $this->request("sender_id");
		if ( $messages = $this->message->listSent($sender_id) ) {
			return $this->response(array("messages" => $messages),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to load messages sent by person id $sender_id."),TRUE);
		}
	}
	
	public function listReceived() 
	{
		$this->load->model("Message_Model","message");
		$recipient_id = $this->request("recipient_id");
		if ( $messages = $this->message->listReceived($recipient_id) ) {
			return $this->response(array("messages" => $messages),FALSE);
		}
		else {
			return $this->response(array("response" => "Unable to load messages received by person id $recipient_id."),TRUE);
		}
	}

}